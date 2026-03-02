<x-mail::message>
# Dropbox Submission Approved

The dropbox submission for {{ $dropbox->full_name }} has been approved.

- **Submission Date:** {{ optional(optional($dropbox->created_at)->timezone(config('app.timezone')))->format('m/d/Y h:i:s A') ?? 'N/A' }}
- **Date of Birth:** {{ optional($dropbox->date_of_birth)->format('m/d/Y') ?? 'N/A' }}
- **Type:** {{ ucfirst($dropbox->type) }}
- **Drug of Choice:** {{ $dropbox->drug_of_choice }}
- **Last Use Date:** {{ optional($dropbox->last_use_date)->format('m/d/Y') ?? 'N/A' }}

@isset($dropbox->remarks)
**Remarks**

{{ $dropbox->remarks }}
@endisset

<x-mail::button :url="route('dropboxes.show', $dropbox)">
Review Dropbox Submission
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
