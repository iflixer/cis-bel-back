@extends('layouts.app')

@section('content')

    <main class="form-main">

        <form action="{{ secure_url('/login') }}" method="post" class="form-in">

            <h1><a href="/">kholobok.biz</a></h1>

            <input type="text" name="login" id="login" value="{{ old('login') }}" placeholder="Логин">
            <input type="password" name="password" id="password" placeholder="Пароль">

            {{ csrf_field() }}

            {{ $errors->first('status') }}

            <button>Войти</button>

            @if ($errors->has('login'))
                <div class="error-form-login">{{ $errors->first('login') }}</div>
            @endif

            @if ($errors->has('password'))
                <div class="error-form-login">{{ $errors->first('password') }}</div>
            @endif
                    
            <a href="{{ secure_url('/register') }}">Регистрация</a>
            <a href="{{ secure_url('/password/reset') }}">Забыли пароль?</a>

        </form>

    </main>

@endsection



