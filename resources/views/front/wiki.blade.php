<script>
    if (document.documentMode) {
        alert('建議使用Edge或者Chrome瀏覽器進行瀏覽')
        // document.getElementById("loading").style.display = "block";

    }
</script>

<!DOCTYPE html>
<html lang="zh-TW" class="html">

<head>
    <meta charset="UTF-8">

    <title>《仙俠世界貳》遊戲百科</title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="Digeam 掘夢網,線上遊戲,免費遊戲,3D">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://xx2.digeam.com/">{{-- 官網連結 --}}
    <meta property="og:title" content="">
    <meta property="article:author" content="https://www.facebook.com">
    <meta property="og:image" content="/img/event/homepage/thumbnail_1200x628.jpg" />


    {{-- 連結縮圖 --}}
    @yield('thumbnail')

    <link rel="icon" href="/img/event/prereg/favicon.ico" sizes="16x16">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
    <link rel="stylesheet" href="/css/event/homepage/wiki.css">

</head>

<body>
    <div class="wrap">

        <div class="main">
            {{-- <div id="loading">
                <div class="loadBox">
                    <p>頁面加載中，請稍等</p>
                    <div class="spinner"></div>
                </div>
            </div> --}}
            <div class="mainBG">
                <div class="topBox">
                    <nav class="leftBox">
                        <a href={{ route('index') }}><img src="/img/event/wiki/LOGO.png"></a>

                        <div class="serch">
                            <form id="serchForm" action="" method="GET">
                                <input type="text" name="keyword" placeholder="搜尋...">
                            </form>
                        </div>

                    </nav>
                    <nav class="iconBox">
                        <a href="https://discord.gg/2ZRW3hacJ2"><img src="/img/event/wiki/social_icon_dc.png"></a>
                        <a href="https://www.facebook.com/DiGeamXianXia2"><img
                                src="/img/event/wiki/social_icon_fb.png"></a>
                    </nav>
                </div>

                <div class="mainBox">
                    <menu class="menu">
                        <div class="menuBox">

                            @foreach ($sec_cate as $value)
                                {{-- 只有大標題有內文 --}}
                                @if ($value['count'] != 0 && $value['parent_id'] == 0)
                                    <ul class="frontTitle" id='title-{{ $all_page[$value['cate_id']][0]['id'] }}'>
                                        <a
                                            href="{{ route('wiki', $all_page[$value['cate_id']][0]['id']) }}">{{ $value['title'] }}</a>
                                    </ul>
                                @endif

                                {{-- 是大標題還有中標題 --}}
                                @if ($value['count'] == 0 && $value['parent_id'] == 0)
                                    <ul class="frontTitle">
                                        <p>{{ $value['title'] }}</p>
                                        @foreach ($sec_cate as $value2)
                                            @if ($value2['parent_id'] == $value['id'])
                                                {{-- 只到中標題 --}}
                                                @if ($value2['count'] == 0)
                                                    <li class="liMiddle"><a
                                                            href="{{ route('wiki') }}">{{ $value2['title'] }}</a>
                                                    </li>
                                                @elseif($value2['count'] == 1)
                                                    <li class="liMiddle"
                                                        id='title-{{ $all_page[$value2['cate_id']][0]['id'] }}'><a
                                                            href="{{ route('wiki', $all_page[$value2['cate_id']][0]['id']) }}">{{ $value2['title'] }}</a>
                                                    </li>
                                                @else
                                                    <li class="liMiddle">
                                                        <p>{{ $value2['title'] }}</p>
                                                        <ul>
                                                            @for ($i = 0; $i < $value2['count']; $i++)
                                                                <li class="liSamll"
                                                                    id='title-{{ $all_page[$value2['cate_id']][$i]['id'] }}'>
                                                                    <a
                                                                        href="{{ route('wiki', $all_page[$value2['cate_id']][$i]['id']) }}">{{ $all_page[$value2['cate_id']][$i]['title'] }}</a>
                                                                </li>
                                                            @endfor
                                                        </ul>
                                                    </li>
                                                @endif
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            @endforeach
                        </div>
                    </menu>
                    <div class="rightBox">
                        <div class="mainTextBox">
                            @if ($type == 'content')
                                <div class="title">{{ $page['title'] }}</div>
                                <div class="line"></div>
                                <div class="text">{!! $page['content'] !!}</div>
                            @endif
                            @if ($type == 'search' && count($result) != 0)
                                @for ($i = 0; $i < count($result); $i++)
                                    <div class="title">{{ $result[$i]['title'] }}</div>
                                    <div class="line"></div>
                                    <div class="text2">{!! $result[$i]['content'] !!}</div>
                                    <button class="continueBtn"
                                        onclick="location.href='{{ route('wiki', $result[$i]['id']) }}'">繼續閱讀
                                        →</button>
                                @endfor
                            @endif
                            @if ($type == 'search' && count($result) == 0)
                                <div class="title">找不到</div>
                                <div class="line"></div>
                                <div class="text2">抱歉，沒東西符合你的搜尋條件。請試試其他不同關鍵字。</div>
                            @endif
                        </div>

                        <footer class="footer">
                            <div class="footerBox">
                                <div class="footerbox_logo">
                                    <a href="https://www.digeam.com/index"><img class="digeamlogo"
                                            src="/img/event/wiki/logo_digeam.png"></a>
                                    <img class="giantlogo" src="/img/event/wiki/GIANT_logo.png">
                                </div>
                                <div class="copyright">
                                    <a href="https://www.digeam.com/terms">會員服務條款</a>
                                    <a href="https://www.digeam.com/terms2">隱私條款</a>
                                    <p>掘夢網股份有限公司©2023 Copyright©DiGeam Corporation.<br>All Rights Reserved.</p>
                                </div>
                                <div class="plus">
                                    <img class="plus15" src="/img/event/wiki/15plus.png">
                                    <p>本遊戲為免費使用，部分內容設計暴力情節及不當言語情節。<br>
                                        遊戲內另提供購買虛擬遊戲幣、物品等付費服務。<br>
                                        請注意遊戲時間，避免沉迷。​<br>
                                        <span class="blue">本遊戲服務區域包含台灣、香港、澳門。</span>
                                    </p>
                                </div>
                            </div>
                        </footer>
                    </div>
                </div>


            </div>
        </div>


    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script>
        // menu點擊、hover
        $(document).ready(function() {
            setTimeout(function() {
                var url = location.href.split("/");
                var id = (url[url.length - 1]);
                var main = $("#" + "title" + "-" + id).parent().attr('class');
                console.log(main);
                if (main != 'menuBox') {
                    var targetP = $("#" + "title" + "-" + id).closest('ul').find('p').text();
                    console.log($('.liMiddle p'));
                    if (targetP != '') {
                        $("#" + "title" + "-" + id).closest('ul').find('p').click();
                        $('.liMiddle ul').show();
                    } else {
                        $("#" + "title" + "-" + id).closest('ul').parent().parent().find('p')[0].click()
                    }
                }
                $("#" + "title" + "-" + id + " a").attr("style", "color:#004dc1;");
            }, 100);

            $('.frontTitle p').on('click', function() {
                var pUl = $(this).parent('ul');
                // var liMiddle = $(this).find('.liMiddle');
                var liMiddle = pUl.find('.liMiddle');
                var displayValue = liMiddle.css("display");
                if (displayValue == "none") {
                    //隱藏
                    $('.liMiddle').hide();
                }
                liMiddle.toggle();
            });
        });

        $('.liMiddle').on('click', function() {
            var liSmall = $(this).find('.liSmall');
            var liMiddleUL = $(this).find('ul');

            var displayValue = liMiddleUL.css("display");
            if (displayValue == "none") {
                liMiddleUL.attr("style", "display:list_item;")
            } else {
                liMiddleUL.attr("style", "display:none;")
            }

        });


        $('li ul li a').click(function(event) {
            if ($(this).attr('href')) {
                window.location = $(this).attr('href');
            }
            event.stopPropagation();
        });

        // serch 按下enter發送資訊
        $(document).ready(function() {
            $('#serchForm input[name="serch"]').on('keypress', function(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    $('#serchForm').submit();
                }
            });
        });
    </script>
</body>

</html>

<script>
    // $(window).on('load', function() {
    //     $("#loading").hide();
    // });
</script>
