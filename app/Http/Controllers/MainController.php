<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Http\Response;

use Session;
use DB;

use App\Main; // Указать модель обработки 


class MainController extends Controller{


    public $request;


    public function __construct(Request $request){
        $this->request = $request;
    }

    public function show(){
        // print_r($request->all());

        // $sessions = Session::all();
        // Session::flash('login', $this->request['login']);




        // return response()->view('nameTemplate', $data);
        // return (new Response('content')->header('key','value'));
        // return response()->json([]);
        // return redirect('/');




        // dump(array); // хелпер отображения


        

        // Запрос к базе без конструктора
        // DB::select('SELECT * FROM `mains` WHERE id = ?', [data]) // id = :id, ['id' => 'data']



        // Обращение к таблице
        // DB::table('nameTable')
        // NameModelTable::
        // | $query = NameModelTable::select('nameColum', 'nameColum');
        // | $query->addSelect('nameColum AS newName')->get(); // Добавить поле в запрос



        // Старт запроса
        // ->get() // Вся информация 
        // ->first() // Первая запись



        // Оператор SELECT
        // ->select('nameColum', 'nameColum')
        // ->distinct()->select('nameColum') // Уникальные значения
        // ->value('nameColum') // Одно поле первой записи
        // ->pluck('nameColum') // Одно поле всех записей
        // ->count() // Количество записей
        // ->max('nameColum') // Максимальное значение в колонке
        // ->chunk(2, function($nameTable){}); // Порционная обработка записей '2' шт


        // Оператор WHERE 
        // ->where( 'поле', 'значение' ) 
        // ->where( 'поле', 'оператор', 'значение' ) // По умолчанию оператор =
        // ->where( 'поле', 'оператор', 'значение', OR | AND ) // По умолчанию оператор AND
        // ->orWhere() // По умолчанию оператор OR
        // | [[where], [where, 'OR']] == where()->orWhere()
        // ->whereIn('nameColum', [value, value]) // Массив значений для поля
        // ->whereNotIn('nameColum', [value, value]) // Массив ненужных значений для поля
        // ->whereBetween('reservation_from', [$from, $to])


        // Операторы GROUP BY и  HAVING
        // ->groupBy('id_video') // Групировка по полю
        // ->having() // Оператор индетичен where, работает для групп
        // ->havingRaw('count(*) = 2') // Быстрая строка сравнения

        // Оператор ORDER BY
        // ->orderBy('created_at', 'DESC')

        // Работа с коллекциями ответов
        // ->toArray() // Коллекцию в массив, и содержимое тоже
        // ->all() // Коллекцию в массив не изменяя содержимое




        

        // DB::table('nameTable')->where('nameColum', 'operator', 'value')->get(); // ->where()->where(,'OR')
        // ->where()->where(,'OR') == [[where], [where, 'OR']] // ->where()->orWhere()
        // ->whereBetween('nameColum', [value, value]) || ->whereNotBetween('nameColum', [value, value])
        // ->whereIn('nameColum', [value, value]) || ->whereNotIn('nameColum', [value, value])
        // ->groupBy('nameColum')
        // DB::table('nameTable')->take(value)->get(); // LIMIT
        // DB::table('nameTable')->skip(value)->get(); // OFFSET

        // Main::find(id); // [id, id]
        // Main::findOrFail(id); // [id, id] // найти ил выдать ошибку
        // Main::where();
        // Main::all();



        // DB::insert('INSERT INTO `mains` (`text`) VALUES (?)', ['test']);
        // DB::connection()->getPdo()->lastInsertId(); // Id вставленной записи
        // DB::table('nameTable')->insert([ ['nameColum'=>'value'], ['nameColum'=>'value'] ]);
        // DB::table('nameTable')->insertGetId([ ['nameColum'=>'value'], ['nameColum'=>'value'] ]); // Id вставленной записи

        // Main::create(['text'=>'new']);
        // $ = new Main  >>  $->nameColum =  >>  $->save();
        // Main::firstOrCreate(['nameColum'=>'value']) // Вставка уникальных значений иначе возращает модель



        // DB::update('UPDATE `mains` SET `text` = ? WHERE `id` = ?', ['test new', 2]);
        // DB::table('nameTable')->where()->update([ ['nameColum'=>'value'], ['nameColum'=>'value'] ])
        // DB::table('nameTable')->increment('nameColum', value) // Для чисел // decrement [-]

        // Main::  >>  constructor sql
        // $ = Main::find(id)  >>  $->nameColum =  >>  $->save();


        // DB::delete('DELETE FROM `mains` WHERE `id` = ?', [2]);
        // DB::table('nameTable')->where('id', 2)->delete();
        // $ = Main::[select]  >>  $->delete();
        // Main::destroy(id); // [id, id]

        // DB::statement('DROP table `mains`');

        // DB::listen(function ($query){ dump($query->sql) });


        // use Auth;
        // Auth::check() // Проверка на аунтификацию пользователя
        // Auth::id() // id пользователя
        // Auth::attempt(['login'=>$, 'password'=>$])  >>  $ = $request->all();  >>  rezult(bolean) // Авторизация пользователей
        // return redirect()->intended(); // Направить на страницу предедущей попытки входа
        // return redirect()->back();
        // ->withInput( [$request->only('nameInput')] ); // Сохранить введеные данные
        // ->withErrors( ['nameInput'=>'messej']); // Передать в сессию ошибки
        // Auth::login($user); // Логирование без ввода данных
        // Auth::guard('web' [api] )->login($user) // Указание охранника системы
        // ->logout();
        // Auth::loginUsingId($idUser); // Логирование по id
        // Auth::once(['login'=>$, 'password'=>$])  >>  $ = $request->all();  >>  rezult(bolean) // Авторизация пользователей на один запрос


        // use Gate;
        // Gate::denies('nameGate');  >>  rezult(bolean) // Проверка прав пользователя
        // Gate::allows('nameGate'); // если разрешено
        // | return redirect()->back()->with(['message'=>'У вас нет прав']);





        // if($this->request->isMethod('post')){
        //     $this->request->flash();
        // }

        // $data['db'] = function () { dump( DB::select('SELECT * FROM `mains`') ); };

        // $data['sessions'] = function () { print_r( Session::all() ); };
        // //$data['request'] = function () { print_r($this->request->all()); };


        // $data['header'] = function () { echo view('templates/header')->render(); };
        // $data['footer'] = function () { echo view('templates/footer')->render(); };

        // return view('in', $data);

        return response()->json([ 'method' => 'registr', 'operation' => true ]);

    }

}
