@extends('layouts.app')

@section('content')
    <main class="form-main">

        <form action="{{ secure_url('/register') }}" method="post" class="form-in">
            <h1><a href="/">kholobok.biz</a></h1>
            <p>Регистрация</p>

            <input id="name" type="text" class="form-control" name="login" value="{{ old('login') }}" placeholder="Логин">
            @if ($errors->has('name'))
                <strong>{{ $errors->first('name') }}</strong>
            @endif

            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="E-mail">
            @if ($errors->has('email'))
                <strong>{{ $errors->first('email') }}</strong>
            @endif

            <input id="password" type="password" class="form-control" name="password" placeholder="Пароль">
            @if ($errors->has('password'))
                <strong>{{ $errors->first('password') }}</strong>
            @endif

            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="Повторите пароль">
            @if ($errors->has('password_confirmation'))
                <strong>{{ $errors->first('password_confirmation') }}</strong>
            @endif

            {{ csrf_field() }}

            <button>Зарегистрироваться</button>

            <a href="{{ secure_url('/login') }}">Войти</a>
        </form>

    </main>
@endsection
