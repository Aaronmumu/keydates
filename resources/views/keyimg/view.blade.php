<html>
<body>
<style type="text/css">
    h1 {padding-left: 40px;}
    ul li {display:block;float: left;border-radius: 5px;margin: 0px 10px 10px 0px;}
    ul li img {border-radius: 5px;}
</style>
    <h1>Hello, {{ $name }}</h1>
    <ul>
        <? foreach ($data as $datum){?>
        <li>
            <img width="<? echo $datum['view']['w']?>" height="<? echo $datum['view']['h']?>" src='<? echo asset('storage/' . basename($datum['url']));?>'/>
            <? echo '<pre>';print_r($datum['info'][1]['exif']['IFD0']['DateTime'])?>
        </li>
        <? }?>
    </ul>
</body>
</html>