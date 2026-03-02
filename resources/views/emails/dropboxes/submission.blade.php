<x-mail::message>
# New Dropbox Submission

A new {{ $dropbox->type }} dropbox form has been submitted.

- **Name:** {{ $dropbox->full_name }}
- **Date of Birth:** {{ optional($dropbox->date_of_birth)->format('m/d/Y') ?? 'N/A' }}
- **Submitted At:** {{ optional(optional($dropbox->created_at)->timezone(config('app.timezone')))->format('m/d/Y h:i:s A') ?? 'N/A' }}
- **Returning Client:** {{ $dropbox->returning_client ? 'Yes' : 'No' }}
- **Currently in Program:** {{ $dropbox->currently_in_program ? 'Yes' : 'No' }}
- **Program Name:** {{ $dropbox->program_name ?? 'Not Provided' }}
- **Drug of Choice:** {{ $dropbox->drug_of_choice }}
- **Last Use Date:** {{ optional($dropbox->last_use_date)->format('m/d/Y') ?? 'N/A' }}

@isset($dropbox->notes)
> {{ $dropbox->notes }}
@endisset

<x-mail::button :url="route('dropboxes.show', $dropbox)">
View Submission
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
