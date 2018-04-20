@component('mail::message')
# Unable to fetch the feed

@if(!$has_auth)
It seems your authentication with Instagram has been invalidated. You will need to grant access to the site before it can refresh your feed.
@else
An error occurred while refreshing your instagram feed. You may want to investigate further.
@endif

Thanks.

Thanks,<br>
{{ config('app.name') }}
@endcomponent