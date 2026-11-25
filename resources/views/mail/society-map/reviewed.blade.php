@component('mail::message')
@if ($published)
# Your map is live

Hello {{ $submission->contact_name }},

Thank you for sending us the map for **{{ $submission->society_name }}**. It has been checked and is now published on {{ config('app.name') }}, where buyers can view it in detail and download a copy.

@component('mail::button', ['url' => $mapUrl])
View the Published Map
@endcomponent

If anything on the page needs correcting, just reply to this email and we will put it right.
@else
# About the map you sent us

Hello {{ $submission->contact_name }},

Thank you for sending us the map for **{{ $submission->society_name }}**. We are not able to publish it as it stands.

@component('mail::panel')
**Why**

{{ $submission->review_note }}
@endcomponent

You are welcome to send it again once that is sorted — reply to this email if anything is unclear, and we will help.
@endif

Your reference is **SM-{{ str_pad((string) $submission->id, 5, '0', STR_PAD_LEFT) }}**.

Thanks,<br>
{{ config('app.name') }}

@slot('subcopy')
You are receiving this because a society map was submitted with this email address on {{ config('app.name') }}.
@endslot
@endcomponent
