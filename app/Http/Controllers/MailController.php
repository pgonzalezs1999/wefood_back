<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;

class MailController extends Controller
{
    public static function verifyEmail(string $email='', string $token='') {
        $verifyEmail_url = 'http://127.0.0.1:8000/api/auth/verifyEmail';
        $verifyEmail_body = '
            <h1>¡Has iniciado sesión!</h1>
            <p>Gracias por iniciar sesión en nuestro sitio.</p>
            <form id="verifyEmailForm" action="' . $verifyEmail_url . '" method="POST">
                <input type="hidden" name="_token" value="' . $token . '">
            </form>
            <button onclick="submitForm()">Ir a algún lugar</button>
            <script>
                function submitForm() {
                    document.getElementById("verifyEmailForm").submit();
                }
            </script>
        ';
        //Mail::send([], [], function($message) use ($email, $verifyEmail_body) {
         //   $message -> to($email)
           //          -> subject('Inicio de sesión exitoso')
             //        -> html($verifyEmail_body, 'text/html');
        //});
    }
}