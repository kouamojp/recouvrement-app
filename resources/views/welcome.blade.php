<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Arcreances - Gestion des créances</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fffff9;
            color: #636b6f;
            font-family: 'Open sans', sans-serif ;
            font-weight: 700;
            height: 100vh;
            margin: 0;
            padding: 0 1rem;
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

        /* .title a {
            font-family: "DM Sans", sans-serif !important;
            font-weight: 900;

        } */

        .title img{
            width: 100%;
            max-width: 50rem;
        }

        @media only screen and (max-width: 767px){
           .title img{
            width: 100%;
        }
    }

    .links{
        margin-top: 30px;
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        flex-wrap: wrap;


        .nav-link {
            color: #fff;
            padding: 1rem 2rem;
            font-size: 18px;
            font-weight: 600;
            background-color: #000;
            text-decoration: none;
            text-transform: uppercase;
            border-radius: 5px;
            width: auto;
            transition: all 0.3s ease-in-out;
            @media(max-width: 767px){
                width: 100%;
                text-align: center;
            }
        }

        .nav-link:hover {
            background-color: #fda63a;
            transition: all 0.3s ease-in-out;
        }
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
                        <div class="logo">
                            <img src="{{ URL::to('/img/logo-2.png') }}">
                        </div>

                        <div class="links">

                            <a class="nav-link btn" href="http://app-arcreances.proditech-digital.com/" target="_blank"> Mes dettes </a>
                            <a class="nav-link btn" href="http://app-arcreances.proditech-digital.com/" target="_blank"> Mes creances </a>
                        </div>
                    </div>
                </div>
            </body>
            </html>
