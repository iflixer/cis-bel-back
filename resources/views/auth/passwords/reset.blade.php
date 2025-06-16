@extends('layouts.app')

@section('content')

<main class="form-main">

    <form class="form-in" role="form" method="POST" action="{{ secure_url('/password/reset') }}">
        <h1><a href="/">kholobok.biz</a></h1>
        <p>Восстановление пароля</p>
        

        <input type="hidden" name="token" value="{{ $token }}">

        <input id="email" type="email" class="form-control" name="email" value="{{ $email or old('email') }}" placeholder="E-mail">
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


        <button>Восстановить</button>
    </form>

</main>
@endsection
