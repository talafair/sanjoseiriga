@php
  $prefix = $formPrefix ?? '';
  $student = old('is_student', $user->is_student ?? false);
  $outOfSchool = old('is_out_of_school_youth', $user->is_out_of_school_youth ?? false);
  $level = old('student_level', $user->student_level ?? null);
  $savedSchool = old('school', $user->school ?? null);
  $schoolKey = array_search($savedSchool, \App\Models\User::SCHOOLS, true);
  $schoolOther = old('school_other', $user->school_other ?? null);
@endphp
<div class="w-100"></div>
<div class="col-12 col-md-4">
  <input type="hidden" name="is_student" value="0">
  <input type="hidden" name="is_out_of_school_youth" value="0">
  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="is_student" value="1" id="{{ $prefix }}is_student" @checked($student)>
    <label class="form-check-label" for="{{ $prefix }}is_student">Student</label>
  </div>
  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="is_out_of_school_youth" value="1" id="{{ $prefix }}is_out_of_school_youth" @checked($outOfSchool)>
    <label class="form-check-label" for="{{ $prefix }}is_out_of_school_youth">Out-of-school Youth</label>
  </div>
  <input type="hidden" name="is_4ps_member" value="0">
  <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_4ps_member" value="1" id="{{ $prefix }}is_4ps_member" @checked(old('is_4ps_member', $user->is_4ps_member ?? false))><label class="form-check-label" for="{{ $prefix }}is_4ps_member">4Ps member</label></div>
  <input type="hidden" name="is_pwd" value="0">
  <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_pwd" value="1" id="{{ $prefix }}is_pwd" @checked(old('is_pwd', $user->is_pwd ?? false))><label class="form-check-label" for="{{ $prefix }}is_pwd">Person with Disability (PWD)</label></div>
  <input type="hidden" name="is_solo_parent" value="0">
  <div class="form-check"><input class="form-check-input" type="checkbox" name="is_solo_parent" value="1" id="{{ $prefix }}is_solo_parent" @checked(old('is_solo_parent', $user->is_solo_parent ?? false))><label class="form-check-label" for="{{ $prefix }}is_solo_parent">Solo parent</label></div>
  <input type="hidden" name="is_sjtoda" value="0">
  <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_sjtoda" value="1" id="{{ $prefix }}is_sjtoda" @checked(old('is_sjtoda', $user->is_sjtoda ?? false))><label class="form-check-label" for="{{ $prefix }}is_sjtoda">SJTODA</label></div>
  <input type="hidden" name="is_bpc" value="0">
  <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_bpc" value="1" id="{{ $prefix }}is_bpc" @checked(old('is_bpc', $user->is_bpc ?? false))><label class="form-check-label" for="{{ $prefix }}is_bpc">BPC</label></div>
</div>

<div class="col-12 col-md-4" id="{{ $prefix }}student_level_wrap" @if(!$student) hidden @endif>
  <label class="form-label" for="{{ $prefix }}student_level">Level</label>
  <select name="student_level" id="{{ $prefix }}student_level" class="form-select">
    <option value="">Select level</option>
    @foreach (\App\Models\User::STUDENT_LEVELS as $value => $label)
      <option value="{{ $value }}" @selected($level === $value)>{{ $label }}</option>
    @endforeach
  </select>
</div>
<div class="col-12 col-md-4" id="{{ $prefix }}school_wrap" @if(!$student) hidden @endif>
  <label class="form-label" for="{{ $prefix }}school">Name of school</label>
  <input name="school" id="{{ $prefix }}school" value="{{ $savedSchool }}" class="form-control" list="{{ $prefix }}school_options" placeholder="Search or type a school">
  <datalist id="{{ $prefix }}school_options">
    @foreach (\App\Models\User::SCHOOLS as $value => $label)
      <option value="{{ $label }}"></option>
    @endforeach
  </datalist>
  <input name="school_other" id="{{ $prefix }}school_other" value="{{ $schoolOther }}" class="form-control mt-2" placeholder="Enter school name" @if($savedSchool !== 'Other') hidden @endif>
</div>
@push('scripts')
<script>
(function () {
  const student = document.getElementById(@json($prefix . 'is_student'));
  const outOfSchool = document.getElementById(@json($prefix . 'is_out_of_school_youth'));
  const levelWrap = document.getElementById(@json($prefix . 'student_level_wrap'));
  const schoolWrap = document.getElementById(@json($prefix . 'school_wrap'));
  const level = document.getElementById(@json($prefix . 'student_level'));
  const school = document.getElementById(@json($prefix . 'school'));
  const schoolOther = document.getElementById(@json($prefix . 'school_other'));
  if (!student || !outOfSchool) return;
  function sync() {
    if (student.checked) outOfSchool.checked = false;
    if (outOfSchool.checked) student.checked = false;
    levelWrap.hidden = !student.checked;
    schoolWrap.hidden = !student.checked;
    level.required = student.checked;
    school.required = student.checked;
    level.disabled = !student.checked;
    school.disabled = !student.checked;
    schoolOther.disabled = !student.checked || school.value !== 'Other';
    schoolOther.required = student.checked && school.value === 'Other';
    schoolOther.hidden = school.value !== 'Other';
  }
  student.addEventListener('change', sync);
  outOfSchool.addEventListener('change', sync);
  school.addEventListener('change', sync);
  sync();
})();
</script>
@endpush
