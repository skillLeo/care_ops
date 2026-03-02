@component('mail::message')
# Authorization to Release Information

Hello {{ $dropbox->first_name }},

Thank you for completing the form. Attached is a copy of your signed **Authorization to Release Information** for your records.

**Documents Needed from your current program:**
- Biopsychosocial
- Last 4 urine history
- Discharge summary

Please share the attached authorization letter with your current program to request these documents and send them to {{ env('DROPBOX_CONTACT_MAIL', 'intake@snbllc.org') }}.

If you have any questions, please contact us at {{ env('DROPBOX_CONTACT_MAIL', 'intake@snbllc.org') }}.

Thanks,
{{ config('app.name') }}
@endcomponent
