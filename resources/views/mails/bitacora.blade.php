@component('mail::message')

{{ $message }}


@component('mail::table')
|        |          |
| ------------- |:-------------:| 
@if($cambio_estado)
| **Estado Anterior**      | {{ $estado_anterior }}      | 
| **Estado Actual**      | {{ $estado_actual }} |
@endif
| **Responsable**      | {{ $responsable }} |
| **Comentario**      | {{ $comentario }} |

@endcomponent

@component('mail::button', ['url' => 'muniguate.com', 'color' => 'success'])
Ver
@endcomponent

@endcomponent