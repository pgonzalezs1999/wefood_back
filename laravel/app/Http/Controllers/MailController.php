<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;

class MailController extends Controller
{
    public static function verifyEmail(string $email) {
        
        $verifyEmail_body = '
            <h1>¡Has iniciado sesión!</h1>
            <p>Gracias por iniciar sesión en nuestro sitio.</p>
            <a href=""
                style="padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none;">Ir a algún lugar
            </a>
        ';
        Mail::send([], [], function($message) use ($email, $verifyEmail_body) {
            $message -> to($email)
                     -> subject('Inicio de sesión exitoso')
                     -> html($verifyEmail_body, 'text/html');
        });
    }
}