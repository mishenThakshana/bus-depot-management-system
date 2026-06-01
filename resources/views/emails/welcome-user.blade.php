<x-mail::message>
# Welcome to the Bus Depot Management System

Hi **{{ $user->name }}**,

An account has been created for you on the **Bus Depot Management System**. You can log in using the credentials below.

<x-mail::table>
| | |
|:--|:--|
| **Email** | {{ $user->email }} |
| **Temporary Password** | `{{ $temporaryPassword }}` |
| **Role** | {{ $user->getRoleLabel() }} |
</x-mail::table>

For security, you will be asked to set a new password the first time you log in.

<x-mail::button :url="$loginUrl">
Log In Now
</x-mail::button>

If you did not expect this email or believe it was sent in error, please ignore it or contact your system administrator.

Thanks,
**{{ config('app.name') }} Team**
</x-mail::message>
