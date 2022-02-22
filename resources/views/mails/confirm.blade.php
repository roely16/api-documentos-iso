@component('mail::message')

{{ $message }}


@component('mail::table')
|        |          |
| ------------- |:-------------:| 
| **Nombre**      | {{ $nombre }}      | 
| **Código**      | {{ $codigo }} |
| **Tipo**      | {{ $tipo }} |
| **Versión**      | {{ $version }} |
| **Elaborado Por**      | {{ $elaborado_por }} |

@endcomponent

@component('mail::button', ['url' => 'muniguate.com', 'color' => 'success'])
Ver
@endcomponent

@endcomponent