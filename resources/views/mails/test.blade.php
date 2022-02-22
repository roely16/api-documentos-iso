@component('mail::message')

# NUEVO DOCUMENTO.   
 
Estimado (a) se le informa que se ha subido un nuevo documento en la plataforma de Documentos ISO.  Los datos del documento agregado son los siguientes:  


@component('mail::table')
|        |          |
| ------------- |:-------------:| 
| **Nombre**      | Centered      | 
| **Código**      | Right-Aligned |
| **Tipo**      | Right-Aligned |
| **Versión**      | Right-Aligned |
| **Elaborado Por**      | Right-Aligned |

@endcomponent

@component('mail::button', ['url' => 'muniguate.com', 'color' => 'success'])
Ver
@endcomponent

@endcomponent