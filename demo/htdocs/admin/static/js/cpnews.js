var cp = {};
cp.news_remote_url = "http://cpnews.duapp.com/json.php?callback=?"
cp.message = function(){
    $.getJSON(cp.news_remote_url, function(data){
        if(! data.content) {
            data.content = '欢迎登录';
        }
        $("#cp_news").html(data.content);
    });
}
cp.message();
