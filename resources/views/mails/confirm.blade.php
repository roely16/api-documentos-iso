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

@component('mail::button', ['url' => 'http://udicat.muniguate.com/apps/documentos-iso/#/', 'color' => 'success'])
Ver
@endcomponent

@endcomponent