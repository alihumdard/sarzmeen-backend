@component('mail::message')
# Thanks for getting in touch

Hello {{ $inquiry->name }},

@if ($listing)
We have received your inquiry about **{{ $listing }}**, and one of our agents will contact you shortly.
@else
We have received your inquiry, and one of our agents will contact you shortly.
@endif

@component('mail::panel')
**Your message**

{{ $inquiry->message }}
@endcomponent

@if ($listingUrl)
@component('mail::button', ['url' => $listingUrl])
View the Listing
@endcomponent
@endif

If anything changes in the meantime, just reply to this email and we will update your request.

Thanks,<br>
{{ config('app.name') }}

@slot('subcopy')
You are receiving this because an inquiry was submitted with this email address on {{ config('app.name') }}.
@endslot
@endcomponent
