
@extends('layouts.app')

@section('content')
    <main class="form-main">
            <form class="form-in" role="form" method="POST" action="{{ secure_url('/password/email') }}">

            <h1><a href="/">kholobok.biz</a></h1>

            <p>Введите ваш E-mail и мы вышлем вам на почту ссылку для восстановления пароля</p>

            @if (session('status'))
                {{ session('status') }}
            @endif
 
    
            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="E-mail">

            @if ($errors->has('email'))
                <strong>{{ $errors->first('email') }}</strong>
            @endif

            {{ csrf_field() }}


            <button>Отправить</button>

        </form>
    </main>
@endsection

