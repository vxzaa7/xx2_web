@extends('front.pageBase')

@section('title')
    <title>《仙俠世界貳》遊戲公告內容</title>
@endsection



@section('textTitle')
    遊戲公告
@endsection



@section('otherCss2')
    <link rel="stylesheet" href="/css/event/homepage/pageAnnouncementContent.css?v=1.0.1">
@endsection




{{-- 顯示當前位置 --}}
@section('seat')
    <span class="currentLocation">最新公告</span>
@endsection


{{-- 內文 --}}
@section('textBox')
    <div class="upBox">
        <div class="title">{{ $page['title'] }}</div>
        <div class="time">{{ $page['created_at'] }}</div>
    </div>
    <div class="line"></div>
    <div class="downBox">
        {!! $page['content'] !!}
    </div>
@endsection
