<?php

namespace Tests\Feature;

use App\Jobs\SendWebPushNotification;
use App\Models\Announcement;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\TalaFairPushNotification;
use App\Services\PushNotificationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_push_subscriptions(): void
    {
        $this->postJson(route('push-subscriptions.store'), $this->subscriptionPayload())
            ->assertUnauthorized();

        $this->deleteJson(route('push-subscriptions.destroy'), [
            'endpoint' => 'https://fcm.googleapis.com/push/endpoint',
        ])->assertUnauthorized();
    }

    public function test_user_can_register_and_update_a_device_subscription(): void
    {
        Queue::fake();
        $this->configureVapid();
        $user = User::factory()->create();
        $payload = $this->subscriptionPayload();

        $this->actingAs($user)->postJson(route('push-subscriptions.store'), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Push notifications are enabled on this device.');

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'subscribable_type' => $user->getMorphClass(),
            'endpoint' => $payload['endpoint'],
            'public_key' => $payload['keys']['p256dh'],
            'auth_token' => $payload['keys']['auth'],
        ]);

        $payload['keys']['p256dh'] = 'B' . str_repeat('C', 80);
        $this->actingAs($user)->postJson(route('push-subscriptions.store'), $payload)->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
            'public_key' => $payload['keys']['p256dh'],
        ]);
    }

    public function test_only_the_subscription_owner_can_remove_a_device_subscription(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $endpoint = $this->subscriptionPayload()['endpoint'];
        $owner->updatePushSubscription($endpoint, 'A' . str_repeat('B', 80), 'C' . str_repeat('D', 20));

        $this->actingAs($otherUser)->deleteJson(route('push-subscriptions.destroy'), [
            'endpoint' => $endpoint,
        ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $owner->id,
            'endpoint' => $endpoint,
        ]);

        $this->actingAs($owner)->deleteJson(route('push-subscriptions.destroy'), [
            'endpoint' => $endpoint,
        ])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_subscription_endpoint_must_use_a_supported_push_service(): void
    {
        Queue::fake();
        $this->configureVapid();
        $user = User::factory()->create();
        $payload = $this->subscriptionPayload();

        $this->actingAs($user)->postJson(route('push-subscriptions.store'), $payload)
            ->assertOk();

        foreach ([
            'https://127.0.0.1/internal',
            'http://fcm.googleapis.com/push/endpoint',
            'https://example.com/push/endpoint',
            'https://fcm.googleapis.com:8443/push/endpoint',
        ] as $endpoint) {
            $payload['endpoint'] = $endpoint;
            $this->actingAs($user)->postJson(route('push-subscriptions.store'), $payload)
            ->assertUnprocessable();
        }

        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    public function test_subscription_registration_requires_complete_vapid_configuration(): void
    {
        Queue::fake();
        $this->configureVapid();
        Config::set('webpush.vapid.subject', null);

        $this->actingAs(User::factory()->create())
            ->postJson(route('push-subscriptions.store'), $this->subscriptionPayload())
            ->assertServiceUnavailable();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_in_app_notification_queues_push_only_for_subscribed_users_without_duplicate_inbox_records(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $announcement = $this->announcement();
        $user->updatePushSubscription(
            $this->subscriptionPayload()['endpoint'],
            'A' . str_repeat('B', 80),
            'C' . str_repeat('D', 20)
        );

        $notification = UserNotification::create([
            'user_id' => $user->id,
            'announcement_id' => $announcement->id,
            'title' => 'New event: ' . $announcement->title,
            'body' => 'You are invited.',
        ]);

        $this->assertDatabaseCount('user_notifications', 1);
        Queue::assertPushed(SendWebPushNotification::class, function (SendWebPushNotification $job) use ($user, $announcement): bool {
            return $job->userId === $user->id
                && $job->title === 'New event: ' . $announcement->title
                && $job->url === route('announcements.show', $announcement);
        });

        $unsubscribedUser = User::factory()->create();
        PushNotificationDelivery::queueForRecipients(
            [$unsubscribedUser->id],
            'Update',
            'An update for you.',
            route('notifications.index')
        );

        Queue::assertPushed(SendWebPushNotification::class, 1);
        $this->assertNotNull($notification->fresh());
    }

    public function test_bulk_delivery_targets_only_users_with_a_push_subscription(): void
    {
        Queue::fake();
        $subscribedUser = User::factory()->create();
        $unsubscribedUser = User::factory()->create();
        $subscribedUser->updatePushSubscription(
            $this->subscriptionPayload()['endpoint'],
            'A' . str_repeat('B', 80),
            'C' . str_repeat('D', 20)
        );

        PushNotificationDelivery::queueForRecipients(
            [$subscribedUser->id, $unsubscribedUser->id],
            'Community update',
            'An update for you.',
            route('notifications.index')
        );

        Queue::assertPushed(SendWebPushNotification::class, 1);
        Queue::assertPushed(SendWebPushNotification::class, fn (SendWebPushNotification $job): bool => $job->userId === $subscribedUser->id);
    }

    public function test_delivery_sends_a_web_push_message_with_the_authorized_destination(): void
    {
        Queue::fake();
        $this->configureVapid();
        $user = User::factory()->create();
        $announcement = $this->announcement();
        $user->updatePushSubscription(
            $this->subscriptionPayload()['endpoint'],
            'A' . str_repeat('B', 80),
            'C' . str_repeat('D', 20)
        );

        Notification::fake();
        $job = new SendWebPushNotification(
            $user->id,
            'New event: ' . $announcement->title,
            'You are invited.',
            route('announcements.show', $announcement)
        );
        $job->handle();

        Notification::assertSentTo($user, TalaFairPushNotification::class, function (TalaFairPushNotification $notification) use ($user, $announcement): bool {
            $payload = $notification->toWebPush($user, $notification)->toArray();

            return $payload['title'] === 'New event: ' . $announcement->title
                && $payload['tag'] === 'talafair-update-' . $notification->id
                && $payload['data']['url'] === route('announcements.show', $announcement);
        });
    }

    private function configureVapid(): void
    {
        Config::set('webpush.vapid.subject', 'https://talafair.example');
        Config::set('webpush.vapid.public_key', 'public-key');
        Config::set('webpush.vapid.private_key', 'private-key');
    }

    private function announcement(): Announcement
    {
        return Announcement::create([
            'title' => 'Community meeting',
            'category' => 'events',
            'body' => 'Meeting details.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionPayload(): array
    {
        return [
            'endpoint' => 'https://fcm.googleapis.com/push/endpoint',
            'keys' => [
                'p256dh' => 'A' . str_repeat('B', 80),
                'auth' => 'C' . str_repeat('D', 20),
            ],
            'contentEncoding' => 'aes128gcm',
        ];
    }
}
