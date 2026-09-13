<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Auditable;

    public const CATEGORIES = [
        'resident' => 'Resident',
        'guest' => 'Guest',
        'official' => 'Official',
    ];

    public const STUDENT_LEVELS = [
        'elementary' => 'Elementary',
        'junior_high_school' => 'Junior High School',
        'senior_high_school' => 'Senior High School',
        'college' => 'College',
        'graduate_school' => 'Graduate School',
        'vocational' => 'Vocational / Technical',
    ];

    public const SCHOOLS = [
        'university_of_saint_anthony' => 'University of Saint Anthony (USANT)',
        'university_of_northeastern_philippines' => 'University of Northeastern Philippines (UNEP)',
        'la_consolacion_college_iriga' => 'La Consolacion College - Iriga',
        'regina_mondi_college' => 'Regina Mondi College, Inc.',
        'oliveros_college' => 'Oliveros College, Inc.',
        'ceguera_technological_colleges' => 'Ceguera Technological Colleges, Inc.',
        'aclc_college_iriga' => 'ACLC College of Iriga',
        'iriga_city_science_high_school' => 'Iriga City Science High School',
        'rinconada_national_technical_vocational_school' => 'Rinconada National Technical Vocational School',
        'fatima_integrated_farm_school' => 'Fatima Integrated Farm School',
        'holy_child_educational_learning_center' => 'Holy Child Educational Learning Center, Inc.',
        'bicol_trinity_mission_learning_center' => 'Bicol Trinity Mission Learning Center, Inc.',
        'san_agustin_stand_alone_senior_high_school' => 'San Agustin Stand Alone Senior High School',
        'san_antonio_national_high_school' => 'San Antonio National High School',
        'san_francisco_national_high_school' => 'San Francisco National High School',
        'san_pedro_national_high_school' => 'San Pedro National High School',
        'sagrada_national_high_school' => 'Sagrada National High School',
        'santo_nino_national_high_school' => 'Santo Niño National High School',
        'santa_maria_national_high_school' => 'Santa Maria National High School',
        'zeferino_arroyo_national_high_school' => 'Zeferino Arroyo National High School',
        'perpetual_help_national_high_school' => 'Perpetual Help National High School',
        'cspc' => 'Camarines Sur Polytechnic Colleges (CSPC)',
        'nabua_national_high_school' => 'Nabua National High School',
        'la_purisima_national_high_school' => 'La Purisima National High School',
        'malawag_national_high_school' => 'Malawag National High School',
        'lourdes_provincial_high_school' => 'Lourdes Provincial High School',
        'tandaay_provincial_high_school' => 'Tandaay Provincial High School',
        'victor_bernal_provincial_high_school' => 'Victor Bernal Provincial High School',
        'san_jose_integrated_school' => 'San Jose Integrated School',
        'sto_domingo_institute' => 'Sto. Domingo Institute',
        'montessori_childrens_house' => "Montessori Children's House of Learning",
        'moreh_asia_pacific_academy' => 'Moreh Asia Pacific Academy',
        'cbsua' => 'Central Bicol State University of Agriculture (CBSUA)',
        'pili_capital_college' => 'Pili Capital College, Inc.',
        'philippine_computer_foundation_college' => 'Philippine Computer Foundation College, Inc. - Pili',
        'universidad_de_sta_isabel_pili' => 'Universidad de Sta. Isabel - Pili Campus',
        'camarines_science_oriented_high_school' => 'Camarines Science Oriented High School',
        'camarines_sur_sports_academy' => 'Camarines Sur Sports Academy',
        'pili_national_high_school' => 'Pili National High School',
        'bikol_high_school_for_arts_and_culture' => 'Bikol High School for the Arts and Culture',
        'computer_science_high_school_of_bicolandia' => 'Computer Science High School of Bicolandia',
        'gov_mariano_villafuerte_high_school' => 'Gov. Mariano E. Villafuerte High School',
        'san_jose_pili_national_high_school' => 'San Jose Pili National High School',
        'rodriguez_national_high_school' => 'Rodriguez National High School',
        'altamarino_clasio_high_school' => 'Altamarino-Clasio High School',
        'binobong_high_school' => 'Binobong High School',
        'binauaanan_high_school' => 'Binauaanan High School',
        'sagurong_high_school' => 'Sagurong High School',
        'v_bagasina_memorial_high_school' => 'V. Bagasina Sr. Memorial High School',
        'blessed_name_of_mary_learning_school' => 'Blessed Name of Mary Learning School',
        'yobhel_christian_academy' => 'Yobhel Christian Academy',
        'st_louise_de_marillac_school' => 'St. Louise de Marillac School',
        'pili_parochial_school' => 'Pili Parochial School',
        'la_consolacion_college_baao' => 'La Consolacion College Baao',
        'baao_community_college' => 'Baao Community College',
        'rosary_school' => 'Rosary School, Inc.',
        'sta_monica_academy' => 'Sta. Monica Academy',
        'ateneo_de_naga' => 'Ateneo de Naga University',
        'biscast' => 'Bicol State College of Applied Sciences and Technology (BISCAST)',
        'city_college_of_naga' => 'City College of Naga',
        'naga_college_foundation' => 'Naga College Foundation, Inc.',
        'university_of_nueva_caceres' => 'University of Nueva Caceres',
        'universidad_de_sta_isabel' => 'Universidad de Sta. Isabel',
        'sti_college_naga' => 'STI College Naga',
        'naga_view_adventist_college' => 'Naga View Adventist College',
        'camarines_sur_national_high_school' => 'Camarines Sur National High School',
        'naga_city_science_high_school' => 'Naga City Science High School',
        'naga_city_school_of_arts_and_trades' => 'Naga City School of Arts and Trades',
        'other' => 'Other',
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            foreach ([
                'name', 'first_name', 'middle_name', 'last_name', 'suffix', 'gender_other', 'gender_identity_other',
                'school', 'school_other', 'occupation', 'street', 'barangay', 'city', 'province',
                'country', 'head_of_family_name',
            ] as $field) {
                if ($user->getAttribute($field) !== null) {
                    $value = trim((string) $user->getAttribute($field));
                    $user->setAttribute($field, $value === '' ? null : Str::title($value));
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'first_name', 'middle_name', 'last_name', 'suffix',
        'username', 'email', 'password', 'role', 'official_group', 'official_position', 'is_verified', 'points',
        'gender', 'gender_other', 'sex_at_birth', 'preferred_gender_identity', 'gender_identity_other', 'birthdate', 'contact_number',
        'is_student', 'student_level', 'is_pwd', 'is_4ps_member', 'is_solo_parent', 'is_out_of_school_youth', 'is_lgbtqia', 'school', 'school_other', 'occupation',
        'house_no', 'street', 'zone', 'barangay', 'city', 'province', 'country', 'postal_code',
        'is_head_of_family', 'head_of_family_name', 'head_of_family_id',
        'unique_id', 'avatar_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'birthdate'         => 'date',
            'is_head_of_family' => 'boolean',
            'is_verified'      => 'boolean',
            'is_student'       => 'boolean',
            'is_pwd'           => 'boolean',
            'is_4ps_member'    => 'boolean',
            'is_solo_parent'   => 'boolean',
            'is_out_of_school_youth' => 'boolean',
            'is_lgbtqia'       => 'boolean',
        ];
    }

    /* ------------------------------------------------------------------
     | Accessors
     * ------------------------------------------------------------------*/

    public function getFullNameAttribute(): string
    {
        return trim(collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])->filter()->implode(' ')) ?: (string) $this->name;
    }

    /** "Dela Cruz, Juan P." — useful for lists and the ID card back. */
    public function getListNameAttribute(): string
    {
        $mi = $this->middle_name ? ' ' . strtoupper(substr($this->middle_name, 0, 1)) . '.' : '';

        return trim("{$this->last_name}, {$this->first_name}{$mi} {$this->suffix}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthdate?->age;   // computed from birthdate, never stored
    }

    public function getGenderLabelAttribute(): ?string
    {
        return $this->gender === 'others'
            ? ($this->gender_other ?: 'Others')
            : ($this->gender ? ucfirst($this->gender) : null);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            trim("{$this->house_no} {$this->street}"),
            $this->zone ? "Zone {$this->zone}" : null,
            "Barangay {$this->barangay}",
            $this->city,
            $this->province,
            $this->country,
            $this->postal_code,
        ])->filter()->implode(', ');
    }

    public function getAvatarUrlAttribute(): string
    {
        if (! $this->avatar_path) {
            return asset('images/default-avatar.svg');
        }

        $disk = config('filesystems.uploads_disk', 'public');

        return $disk === 'public'
            ? rtrim(request()->getBaseUrl(), '/') . '/storage/' . ltrim($this->avatar_path, '/')
            : Storage::url($this->avatar_path);
    }

    public function isOfficial(): bool
    {
        return $this->role === 'official' && $this->official_group !== 'personnel';
    }

    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    /* ------------------------------------------------------------------
     | Audience scopes (used to target announcements)
     * ------------------------------------------------------------------*/

    public function scopeYouth(Builder $q): Builder            // 15 - 30 years old
    {
        return $q->whereNotNull('birthdate')
                 ->whereDate('birthdate', '<=', now()->subYears(15))
                 ->whereDate('birthdate', '>',  now()->subYears(31));
    }

    public function scopeSeniors(Builder $q): Builder          // 60 +
    {
        return $q->whereNotNull('birthdate')
                 ->whereDate('birthdate', '<=', now()->subYears(60));
    }

    public function scopeFamilyHeads(Builder $q): Builder
    {
        return $q->where('is_head_of_family', true);
    }

    /** Does this resident fall inside any of the given audience keys? */
    public function belongsToAudience(array $audiences): bool
    {
        if (empty($audiences) || in_array('public', $audiences, true)) {
            return true;
        }

        $age = $this->age;

        foreach ($audiences as $key) {
            $match = match ($key) {
                'students'       => (bool) $this->is_student,
                'women'          => in_array($this->preferred_gender_identity, ['woman', 'transgender_woman'], true)
                    || ($this->preferred_gender_identity === null && $this->sex_at_birth === 'female'),
                'pwd'            => (bool) $this->is_pwd,
                'four_ps'        => (bool) $this->is_4ps_member,
                'solo_parents'   => (bool) $this->is_solo_parent,
                'out_of_school_youth' => (bool) $this->is_out_of_school_youth,
                'lgbtqia'        => (bool) $this->is_lgbtqia,
                'youth'         => $age !== null && $age >= 15 && $age <= 30,
                'senior'        => $age !== null && $age >= 60,
                'family_heads'  => (bool) $this->is_head_of_family,
                'officials'     => $this->isOfficial(),
                default         => false,
            };

            if ($match) {
                return true;
            }
        }

        return false;
    }

    public function audienceQueryFor(array $audiences): Builder
    {
        if (empty($audiences) || in_array('public', $audiences, true)) {
            return static::query();
        }

        return static::where(function (Builder $query) use ($audiences) {
            foreach ($audiences as $audience) {
                match ($audience) {
                    'students' => $query->orWhere('is_student', true),
                    'women' => $query->orWhere(fn ($scope) => $scope
                        ->whereIn('preferred_gender_identity', ['woman', 'transgender_woman'])
                        ->orWhere(fn ($legacy) => $legacy->whereNull('preferred_gender_identity')->where('sex_at_birth', 'female'))),
                    'pwd' => $query->orWhere('is_pwd', true),
                    'four_ps' => $query->orWhere('is_4ps_member', true),
                    'solo_parents' => $query->orWhere('is_solo_parent', true),
                    'out_of_school_youth' => $query->orWhere('is_out_of_school_youth', true),
                    'lgbtqia' => $query->orWhere('is_lgbtqia', true),
                    'youth' => $query->orWhere(fn ($scope) => $scope->youth()),
                    'senior' => $query->orWhere(fn ($scope) => $scope->seniors()),
                    'family_heads' => $query->orWhere(fn ($scope) => $scope->familyHeads()),
                    'officials' => $query->orWhere('role', 'official'),
                    default => null,
                };
            }
        });
    }

        /* ------------------------------------------------------------------
     | Relationships
     * ------------------------------------------------------------------*/

    public function headOfFamily()
    {
        return $this->belongsTo(User::class, 'head_of_family_id');
    }

    public function familyMembers()
    {
        return $this->hasMany(User::class, 'head_of_family_id');
    }

    /** Spin-the-wheel history. AccountController and the home page use this. */
    public function spins()
    {
        return $this->hasMany(SpinHistory::class);
    }

    public function rsvps()
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function raffleEntry()
    {
        return $this->hasOne(RaffleEntry::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class)->withTimestamps()->withPivot('awarded_by', 'award_rank');
    }

    /**
     * TalaFair's own in-app inbox (user_notifications table).
     * Do NOT name this notifications() — that name belongs to the Notifiable trait.
     */
    public function appNotifications()
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function unreadAppNotifications()
    {
        return $this->appNotifications()->whereNull('read_at');
    }
}