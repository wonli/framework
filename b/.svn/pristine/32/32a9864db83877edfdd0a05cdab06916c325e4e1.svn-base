$.namespace("idsoo.share",
function() {
	idsoo.share = function() {
		this.init.apply(this, arguments)
	};
	idsoo.share.prototype = {
		init: function() {
			var that = this;
			$(".share .sina").click(function() {
				that.sina()
			});
			$(".share .tqq").click(function() {
				that.tqq()
			});
			$(".share .douban").click(function() {
				that.douban()
			});
			$(".share .qzone").click(function() {
				that.qzone()
			});
			$(".share .renren").click(function() {
				that.renren()
			})
		},
		sina: function() {
			var url = "http://v.t.sina.com.cn/share/share.php?";
			url += "title=" + $("title").text() + " " + document.location.href;
			url += "&pic=" + $(".single_img img").attr("src");
			url += "&appkey=591275019";
			window.open(encodeURI(url))
		},
		tqq: function() {
			var url = "http://v.t.qq.com/share/share.php?";
			url += "title=" + $("title").text() + " " + document.location.href;
			url += "&appkey=076f20931d7a4e089c0974b65707d05b";
			url += "&pic=" + $(".single_img img").attr("src");
			window.open(encodeURI(url))
		},
		douban: function() {
			var url = "http://shuo.douban.com/!service/share?";
			url += "name=" + $("title").text();
			url += "&href=" + document.location.href;
			url += "&image=" + $(".single_img img").attr("src");
			window.open(encodeURI(url))
		},
		qzone: function() {
			var url = "http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?";
			url += "title=" + $("title").text();
			url += "&url=" + document.location.href;
			url += "&pics=" + $(".single_img img").attr("src");
			window.open(encodeURI(url))
		},
		renren: function() {
			var url = "http://share.renren.com/share/buttonshare.do?";
			url += "link=" + $("title").text();
			url += "&title=" + document.location.href;
			window.open(encodeURI(url))
		}
	}
});
$(document).ready(function() {
	var share = new idsoo.share()
});