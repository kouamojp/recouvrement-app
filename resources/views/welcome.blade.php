<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Arcreances</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fffff9;
            color: #636b6f;
            font-family: 'Merriweather Sans', sans-serif !important;
            font-weight: 700;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 45px;
        }

        .title a {
            font-family: "Helvetica";
            font-weight: 900;

        }

        .title img{
            width: 40%;
        }

        @media only screen and (max-width: 767px){
           .title img{
            width: 100%;
        }   
    }

    .links > a {
        color: #000;
        padding: 0 35px;
        font-size: 16px;
        font-weight: 700;
        /* letter-spacing: 0.1rem;*/
        text-decoration: none;
        text-transform: uppercase;


    }

        /*.m-b-md {
            margin-bottom: 30px;
            }*/

        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            @if (Route::has('login'))
            <div class="top-right links">
                @auth
                <a href="{{ url('/home') }}">Home</a>
                @else
                <a href="{{ route('login') }}">Login</a>

                @if (Route::has('register'))
                <a href="{{ route('register') }}">Register</a>
                @endif
                @endauth
            </div>
            @endif

            <div class="content">
                <div class="title">
                    <div>
                        <div style="margin-bottom: -1%">
                            <img src="{{ URL::to('/img/logo-2.png') }}">
                        </div>

                        <div class="links" style="margin-left: -8%">

                            <a href="http://debiteur.arcreances.com/login" target="_blank"> Mes dettes </a>
                            <a href="http://partenaire.arcreances.com/login" target="_blank"> Mes creances </a>
                        </div>
                    </div>
                </div>
            </body>
            </html>
