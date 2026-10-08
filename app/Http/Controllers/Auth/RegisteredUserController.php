<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\UniqueIdGenerator;
use App\Services\RaffleService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        // Heads of family already registered, so a new member can point to theirs
        $heads = User::familyHeads()
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix', 'house_no', 'street', 'zone']);

        $role = $request->query('role', 'resident');
        abort_unless(array_key_exists($role, User::CATEGORIES), 404);

        return view('pages.register', compact('heads', 'role'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeLegacyGenderInput($request);
        $isGuest = $request->input('role') === 'guest';
        $requiresHouseholdHead = $request->input('role') === 'resident'
            && ! $request->boolean('is_head_of_family');

        $data = $request->validate([
            'first_name'   => ['required', 'string', 'max:255'],
            'middle_name'  => ['nullable', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'suffix'       => ['nullable', 'string', 'max:20'],

            'sex_at_birth' => ['required', Rule::in(['female', 'male', 'prefer_not_to_say'])],
            'preferred_gender_identity' => ['required', Rule::in(['woman', 'man', 'non_binary', 'transgender_woman', 'transgender_man', 'genderqueer', 'self_describe', 'prefer_not_to_say'])],
            'gender_identity_other' => ['nullable', 'required_if:preferred_gender_identity,self_describe', 'string', 'max:255'],
            'is_lgbtqia' => ['nullable', 'boolean'],
            'is_pwd' => ['nullable', 'boolean'],
            'is_4ps_member' => ['nullable', 'boolean'],
            'is_solo_parent' => ['nullable', 'boolean'],
            'is_out_of_school_youth' => ['nullable', 'boolean', Rule::prohibitedIf(fn () => $request->boolean('is_student') && $request->boolean('is_out_of_school_youth'))],

            'birthdate'      => [$isGuest ? 'nullable' : 'required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'contact_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'is_student'     => ['nullable', 'boolean', Rule::prohibitedIf(fn () => $request->boolean('is_out_of_school_youth') && $request->boolean('is_student'))],
            'student_level'  => ['nullable', Rule::requiredIf(fn () => $request->boolean('is_student')), Rule::in(array_keys(User::STUDENT_LEVELS))],
            'school'         => ['nullable', 'required_if:is_student,1', 'string', 'max:255'],
            'school_other'   => ['nullable', Rule::requiredIf(fn () => strcasecmp((string) $request->input('school'), 'other') === 0), 'string', 'max:255'],
            'occupation'     => ['nullable', 'string', 'max:255'],

            'house_no'     => ['required', 'string', 'max:255'],
            'street'       => ['required', 'string', 'max:255'],
            'zone'         => [$isGuest ? 'nullable' : 'required', 'string', 'max:10'],
            'barangay'     => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'city'         => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'province'     => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'country'      => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'postal_code'  => [$isGuest ? 'required' : 'nullable', 'string', 'max:20'],

            'is_head_of_family'   => ['nullable', 'boolean'],
            'head_of_family_id'   => [
                'nullable',
                Rule::requiredIf($requiresHouseholdHead),
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_head_of_family', true)),
            ],
            'head_of_family_name' => ['exclude_if:is_head_of_family,1', 'nullable', 'string', 'max:255'],

            'username' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash', 'unique:users,username'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::CATEGORIES))],
            'official_group' => ['nullable', 'required_if:role,official', 'in:' . implode(',', array_keys(config('talafair.official_positions')))],
            'official_position' => ['nullable', 'required_if:role,official', 'in:' . implode(',', collect(config('talafair.official_positions'))->flatMap(fn ($group) => array_keys($group['positions']))->all())],
        ], [
            'head_of_family_name.required_if' => 'Please name the head of your family.',
            'head_of_family_id.required'      => 'Select a registered Household Head before registering this household member.',
            'head_of_family_id.exists'        => 'Select a valid registered Household Head.',
            'gender_identity_other.required_if' => 'Please specify your gender identity.',
            'contact_number.regex'            => 'Use digits only, with optional +, -, spaces or brackets.',
        ]);

        if ($data['role'] === 'official' && ! in_array($data['official_position'], array_keys(config("talafair.official_positions.{$data['official_group']}.positions")), true)) {
            return back()->withErrors(['official_position' => 'Select a position from the selected official group.'])->withInput();
        }

        if ($data['role'] === 'official') {
            $limit = config("talafair.official_position_limits.{$data['official_group']}.{$data['official_position']}");
            if ($limit !== null && User::where('role', 'official')->where('official_group', $data['official_group'])->where('official_position', $data['official_position'])->count() >= $limit) {
                return back()->withErrors(['official_position' => "The selected position is already filled (maximum {$limit})."])->withInput();
            }
        }

        $isHead = (bool) ($data['is_head_of_family'] ?? false);

        if (! $isHead && $requiresHouseholdHead) {
            $selectedHead = User::familyHeads()->find($data['head_of_family_id']);
            if (! $selectedHead) {
                return back()->withErrors([
                    'head_of_family_id' => 'Select a valid registered Household Head before continuing.',
                ])->withInput();
            }

            $data['head_of_family_name'] = $selectedHead->full_name;
        } elseif (! $isHead) {
            $headQuery = User::where('is_head_of_family', true)
                ->whereRaw('LOWER(TRIM(house_no)) = ?', [strtolower(trim($data['house_no']))])
                ->whereRaw('LOWER(TRIM(street)) = ?', [strtolower(trim($data['street']))]);

            if (filled($data['zone'] ?? null)) {
                $headQuery->whereRaw('LOWER(TRIM(zone)) = ?', [strtolower(trim($data['zone']))]);
            }

            $matchingHead = $headQuery->orderBy('id')->first();
            if ($matchingHead) {
                $data['head_of_family_id'] = $matchingHead->id;
                $data['head_of_family_name'] = $matchingHead->full_name;
            } else {
                $data['head_of_family_id'] = null;
            }
        }

        $user = DB::transaction(function () use ($data, $isHead, $isGuest) {
            $user = User::create([
                'first_name'  => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name'   => $data['last_name'],
                'suffix'      => $data['suffix'] ?? null,
                // keep the legacy `name` column in sync
                'name'        => trim(collect([
                    $data['first_name'], $data['middle_name'] ?? null, $data['last_name'], $data['suffix'] ?? null,
                ])->filter()->implode(' ')),

                'gender'         => in_array($data['sex_at_birth'], ['female', 'male'], true) ? $data['sex_at_birth'] : null,
                'sex_at_birth'   => $data['sex_at_birth'],
                'preferred_gender_identity' => $data['preferred_gender_identity'],
                'gender_identity_other' => $data['preferred_gender_identity'] === 'self_describe' ? $data['gender_identity_other'] : null,
                'is_lgbtqia'     => (bool) ($data['is_lgbtqia'] ?? false),
                'birthdate'      => $data['birthdate'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'is_student'     => (bool) ($data['is_student'] ?? false),
                'student_level'  => $data['is_student'] ? ($data['student_level'] ?? null) : null,
                'is_pwd'         => (bool) ($data['is_pwd'] ?? false),
                'is_4ps_member'  => (bool) ($data['is_4ps_member'] ?? false),
                'is_solo_parent' => (bool) ($data['is_solo_parent'] ?? false),
                'is_out_of_school_youth' => (bool) ($data['is_out_of_school_youth'] ?? false),
                'school'         => $data['is_student'] ? (strcasecmp((string) ($data['school'] ?? ''), 'other') === 0 ? 'Other' : $data['school']) : null,
                'school_other'   => $data['is_student'] && strcasecmp((string) ($data['school'] ?? ''), 'other') === 0 ? ($data['school_other'] ?? null) : null,
                'occupation'     => $data['occupation'] ?? null,

                'house_no'    => $data['house_no'],
                'street'      => $data['street'],
                'zone'        => $data['zone'] ?? null,
                'barangay'    => $isGuest ? $data['barangay'] : 'San Jose',
                'city'         => $isGuest ? $data['city'] : 'Iriga City',
                'province'    => $isGuest ? $data['province'] : 'Camarines Sur',
                'country'     => $isGuest ? $data['country'] : 'Philippines',
                'postal_code' => $isGuest ? $data['postal_code'] : '4431',

                'is_head_of_family'   => $isHead,
                'head_of_family_id'   => $isHead ? null : ($data['head_of_family_id'] ?? null),
                'head_of_family_name' => $isHead ? null : ($data['head_of_family_name'] ?? null),

                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'              => $data['role'],
                'official_group'    => $data['role'] === 'official' ? $data['official_group'] : null,
                'official_position' => $data['role'] === 'official' ? $data['official_position'] : null,
            ]);

            // Z2-26000000001
            $user->forceFill(['unique_id' => UniqueIdGenerator::for($user)])->save();
            RaffleService::ensureEntry($user);

            if ($isHead) {
                $headName = strtolower(trim($user->full_name));
                User::where('is_head_of_family', false)
                    ->whereNull('head_of_family_id')
                    ->whereRaw('LOWER(TRIM(head_of_family_name)) = ?', [$headName])
                    ->whereRaw('LOWER(TRIM(house_no)) = ?', [strtolower(trim($user->house_no))])
                    ->whereRaw('LOWER(TRIM(street)) = ?', [strtolower(trim($user->street))])
                    ->when($user->zone, fn ($query) => $query->whereRaw('LOWER(TRIM(zone)) = ?', [strtolower(trim($user->zone))]))
                    ->update([
                        'head_of_family_id' => $user->id,
                        'head_of_family_name' => $user->full_name,
                    ]);
            }

            return $user;
        });

        event(new Registered($user));

        if ($user->role === 'resident') {
            $officialIds = User::query()
                ->where('role', 'official')
                ->where(function ($query) {
                    $query->whereNull('official_group')->orWhere('official_group', '!=', 'personnel');
                })
                ->pluck('id');

            UserNotification::insert($officialIds->map(fn ($officialId) => [
                'user_id' => $officialId,
                'title' => 'Resident registration needs verification',
                'body' => "{$user->full_name} has registered and is waiting for account verification.",
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('id-card.show')
            ->with('success', $user->isOfficial()
                ? 'Welcome to TalaFair. Your official account is ready.'
                : ($user->isGuest() ? 'Welcome to TalaFair. Guest QR scanning depends on each event official.' : 'Welcome to TalaFair. Here is your resident ID.'));
    }

    private function normalizeLegacyGenderInput(Request $request): void
    {
        if ($request->has('sex_at_birth')) {
            return;
        }

        $legacyGender = $request->input('gender');
        if ($legacyGender === null) {
            return;
        }

        $request->merge([
            'sex_at_birth' => in_array($legacyGender, ['female', 'male'], true) ? $legacyGender : 'prefer_not_to_say',
            'preferred_gender_identity' => match ($legacyGender) {
                'female' => 'woman',
                'male' => 'man',
                default => 'self_describe',
            },
            'gender_identity_other' => $legacyGender === 'others' ? $request->input('gender_other') : null,
        ]);
    }
}