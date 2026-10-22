<div class="border rounded-3 p-3 mb-4 bg-body-tertiary">
  <h5 class="fw-bold">Consent</h5>
  <p>Dear Respondent:</p>
  <p>
    Greetings! We are undergraduate researchers of the Bachelor of Science in Information Systems from
    Camarines Sur Polytechnic Colleges conducting a study entitled “TalaFair: A Gamified QR-Based Attendance
    Tracking and Pointing System for Barangay San Jose, Iriga City, Camarines Sur.” We respectfully invite you
    to participate by answering this evaluation questionnaire about your experience with the TalaFair system.
  </p>
  <p>
    Your participation is voluntary. The information you provide will be treated confidentially and used solely
    for academic and research purposes. Your responses are linked to your TalaFair account for survey
    administration, including preventing duplicate responses and awarding any survey points; authorized survey
    administrators may view them. Your name is excluded from the printed summary unless the survey
    administrator chooses to include names.
  </p>
  <p>We sincerely appreciate your time, cooperation, and valuable participation. Thank you very much!</p>
  <p class="mb-0">
    Respectfully,<br>
    <strong>The Researchers:</strong><br>
    Ynah Marie R. Calibara<br>
    Toby Marcos U. Briñas<br>
    Hazel Anne A. Salud
  </p>
</div>

<div class="form-check border rounded-3 p-3 mb-4">
  <input class="form-check-input" type="checkbox" name="research_consent" id="{{ $researchConsentId }}Agreement" value="1" required @checked(old('research_consent'))>
  <label class="form-check-label" for="{{ $researchConsentId }}Agreement">
    @if ($researchConsentAdmin ?? false)
      I confirm that the respondent voluntarily agreed to participate and allow their responses to be used for academic and research purposes, and understands that their information and responses will be kept confidential.
    @else
      I voluntarily agree to participate in this study and allow my responses to be used for academic and research purposes. I understand that my personal information and responses will be kept confidential.
    @endif
  </label>
</div>
