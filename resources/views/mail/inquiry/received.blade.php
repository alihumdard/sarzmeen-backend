@component('mail::message')
# New Inquiry Received

Hello {{ $notifiable->name }},

You have a new inquiry from **{{ $inquiry->name }}**.

@component('mail::panel')
**Name:** {{ $inquiry->name }}
**Email:** {{ $inquiry->email }}
@if ($inquiry->phone)
**Phone:** {{ $inquiry->phone }}
@endif
@if ($listing)
**Listing:** {{ $listing }}
@endif
**Received:** {{ $inquiry->created_at->format('d M Y, g:i A') }}
@endcomponent

**Message**

{{ $inquiry->message }}

@component('mail::button', ['url' => $actionUrl])
View in Dashboard
@endcomponent

Replying quickly makes a real difference — most buyers contact several agents at once.

Thanks,<br>
{{ config('app.name') }}

@slot('subcopy')
You can reply directly to this email to reach {{ $inquiry->name }} at {{ $inquiry->email }}.
@endslot
@endcomponent
