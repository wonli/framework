(function($) {
    $.fn.extend({
        insertAtCaret: function(myValue) {
            var obj;
            if (typeof this[0].name != 'undefined') {
                obj = this[0];
            } else {
                obj = this;
            }
            if ($.browser.msie) {
                obj.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
                obj.focus();
            } else if ($.browser.mozilla || $.browser.webkit) {
                var startPos = obj.selectionStart;
                var endPos = obj.selectionEnd;
                var scrollTop = obj.scrollTop;
                obj.value = obj.value.substring(0, startPos) + myValue + obj.value.substring(endPos, obj.value.length);
                obj.focus();
                obj.selectionStart = startPos + myValue.length;
                obj.selectionEnd = startPos + myValue.length;
                obj.scrollTop = scrollTop;
            } else {
                obj.value += myValue;
                obj.focus();
            }
        },
        selectRange: function(start, end) {
            return this.each(function() {
                if (this.setSelectionRange) {
                    this.focus();
                    this.setSelectionRange(start, end);
                } else if (this.createTextRange) {
                    var range = this.createTextRange();
                    range.collapse(true);
                    range.moveEnd('character', end);
                    range.moveStart('character', start);
                    range.select();
                }
            });
        },
        editorize: function(options) {
            var default_acl_check = function() {
                if (current_user_id == 0 || current_user_id == '') {
                    showLoginWin();
                    return {
                        'status': false
                    };
                }
                return {
                    'status': true
                };
            };
            var defaults = {
                'acl_check': default_acl_check,
                'submit_callback': null,
                'group_name_text': ''
            };
            var $message = $('<div class="message"></div>');
            $message.hide();
            var smileys = {
                1 : {
                    title: '笑'
                },
                24 : {
                    title: '色色'
                },
                9 : {
                    title: '酷'
                },
                6 : {
                    title: '流泪'
                },
                8 : {
                    title: '抓狂'
                },
                11 : {
                    title: '坏笑'
                },
                4 : {
                    title: '害羞'
                },
                19 : {
                    title: '财迷'
                },
                13 : {
                    title: '猪头'
                },
                25 : {
                    title: '调皮'
                },
                16 : {
                    title: '转眼珠'
                },
                3 : {
                    title: '泪汪汪'
                },
                20 : {
                    title: '星星眼'
                },
                23 : {
                    title: '飞吻'
                },
                18 : {
                    title: '长草'
                },
                2 : {
                    title: '晕死'
                },
                5 : {
                    title: '问号'
                },
                17 : {
                    title: '刚巴德'
                },
                26 : {
                    title: '拒绝'
                },
                7 : {
                    title: '得意'
                },
                22 : {
                    title: '鄙视'
                },
                14 : {
                    title: '猥琐'
                },
                15 : {
                    title: '囧'
                },
                10 : {
                    title: '怒'
                },
                12 : {
                    title: '心碎'
                },
                21 : {
                    title: '白菜'
                },
                27 : {
                    title: '骷髅'
                },
                28 : {
                    title: '泪'
                },
                29 : {
                    title: '汗'
                },
                30 : {
                    title: '么么'
                },
                31 : {
                    title: '如花'
                },
                32 : {
                    title: '思考'
                }
            };
            var $smileys = $('<div class="smileys shadow popup"><ul></ul></div>');
            var $inner = $smileys.find('ul');
            $.each(smileys,
            function(key, smiley) {
                $inner.append('<li class="smiley" id="' + key + '" title="' + smiley.title + '">' + '<img emotion="[' + smiley.title + ']" alt="' + smiley.title + '" src="' + Meilishuo.config.picture_url + 'css/images/face/' + key + '.gif">' + '</li>');
            });
            $smileys.hide();
            var supported_weisites = [{
                name: '淘宝',
                key: 'a1',
                website: 'http://www.taobao.com/'
            },
            {
                name: '凡客',
                key: 'a2',
                website: 'http://www.vancl.com/'
            },
            {
                name: '俏网',
                key: 'a3',
                website: 'http://www.ihush.com/'
            },
            {
                name: '优衣库',
                key: 'a4',
                website: 'http://www.uniqlo.cn/'
            },
            {
                name: '维棉',
                key: 'a5',
                website: 'http://www.vcotton.com/'
            },
            {
                name: '走秀',
                key: 'a6',
                website: 'http://www.xiu.com/'
            },
            {
                name: '牛尔',
                key: 'a22',
                website: 'http://www.naruko.com.cn/'
            },
            {
                name: '有货',
                key: 'a7',
                website: 'http://www.yoho.cn/'
            },
            {
                name: '银泰网',
                key: 'a20',
                website: 'http://www.yintai.com/'
            },
            {
                name: '梦芭莎',
                key: 'a8',
                website: 'http://www.moonbasa.com/'
            },
            {
                name: '麦包包',
                key: 'a9',
                website: 'http://www.mbaobao.com/'
            },
            {
                name: '麦考林',
                key: 'a10',
                website: 'http://www.m18.com/'
            },
            {
                name: '时尚起义',
                key: 'a11',
                website: 'http://www.shishangqiyi.com/'
            },
            {
                name: '钻石小鸟',
                key: 'a12',
                website: 'http://www.zbird.com/'
            },
            {
                name: '爱购',
                key: 'a13',
                website: 'http://www.iiigou.com/'
            },
            {
                name: '乐蜂',
                key: 'a14',
                website: 'http://www.lafaso.com/'
            },
            {
                name: '耀点',
                key: 'a15',
                website: 'http://www.yaodian100.com/'
            },
            {
                name: '呼哈',
                key: 'a16',
                website: 'http://www.wooha.com/'
            },
            {
                name: '第五大道',
                key: 'a19',
                website: 'http://www.5lux.com/'
            },
            {
                name: '邦购',
                key: 'a17',
                website: 'http://www.ibanggo.com/'
            },
            {
                name: '美西',
                key: 'a18',
                website: 'http://www.meici.com/'
            },
            {
                name: '好乐买',
                key: 'a23',
                website: 'http://www.okbuy.com/'
            },
            {
                name: '拍拍',
                key: 'a24',
                website: 'http://www.paipai.com/'
            },
            {
                name: '兰缪',
                key: 'a25',
                website: 'http://www.lamiu.com/'
            },
            {
                name: '高街',
                key: 'a26',
                website: 'http://www.gaojie.com/'
            },
            {
                name: '我友商城',
                key: 'a27',
                website: 'http://www.woyo.com/'
            },
            {
                name: '聚美优品',
                key: 'a28',
                website: 'http://www.jumei.com/'
            },
            {
                name: '视客眼镜网',
                key: 'a29',
                website: 'http://www.sigo.cn/'
            },
            {
                name: '乐淘',
                key: 'a30',
                website: 'http://www.letao.com/'
            },
            {
                name: '星800',
                key: 'a31',
                website: 'http://www.xing800.com/'
            },
            {
                name: '米奇',
                key: 'a32',
                website: 'http://www.miqi.cn/'
            },
            {
                name: '怡时刻',
                key: 'a33',
                website: 'http://www.yishike.com/'
            }];
            var $goods_publisher = $('<div class="goods_publisher shadow popup">' + '<div class="gray" style="height:23px;">请在下面输入宝贝的链接地址</div>' + '<input type="text" class="goods_url" />' + '<input class="notenterit submit" type="button" value="" />' + '<div class="clear"></div>' + '<div class="mt10 gray">看看支持哪些网站的宝贝</div>' + '<ul class="supported gray"></ul>' + '</div>');
            var $supported = $goods_publisher.find('.supported');
            $.each(supported_weisites,
            function(k, v) {
                $supported.append('<li><a class="' + v.key + '" href="' + v.website + '" target="_blank">' + v.name + '</a></li>');
            });
            $goods_publisher.hide();
            var $goods_progress_bar = $('<div class="progress_bar"></div>');
            $goods_progress_bar.hide();
            var $goods_holder = $('<div class="goods_holder shadow holder">' + '<div class="tedit_icon"><div class="cancel cursor r"></div></div>' + '<div class="goodspic left"><img class="goods_pic" /></div>' + '<div class="goodspic_r r"><h3 class="goods_title f14"></h3>' + '<div class="goods_price mt14"></div></div>' + '</div>');
            $goods_holder.hide();
            var $picture_upload = $('<div>' + '<form method="post" enctype="multipart/form-data" action="http://' + Meilishuo.config.domain.upload + '/editor/ajax_upload_picture" target="picture_upload_iframe">' + '<input type="file" size="1" name="attach[]" class="upload_file" />' + '<input type="hidden" name="ajax" value="1" />' + '</form>' + '<iframe src="about:blank" id="picture_upload_iframe" name="picture_upload_iframe" style="display: none;"></iframe>' + '</div>');
            $picture_upload.css({
                height: '0',
                width: '0',
                opacity: '0'
            });
            var $upload_picture_holder = $('<div class="upload_picture_holder holder shadow"><div class="tedit_icon"><div class="cancel cursor r"></div></div><img /></div>');
            $upload_picture_holder.hide();
            var options = $.extend(defaults, options);
            return this.each(function() {
                var twitter_pic_id = false;
                var goods = false;
                $(this).append($message);
                $(this).append($smileys);
                $(this).append($goods_publisher);
                $(this).append($picture_upload);
                var $holder_wrapper = $('<div>');
                $holder_wrapper.css({
                    'position': 'absolute',
                    'z-index': 99
                });
                $holder_wrapper.append($goods_holder);
                $holder_wrapper.append($upload_picture_holder);
                $(this).append($holder_wrapper);
                $goods_holder.find('.cancel').click(function() {
                    goods = false;
                    $goods_holder.hide();
                });
                $upload_picture_holder.find('.cancel').click(function() {
                    twitter_pic_id = false;
                    $upload_picture_holder.hide();
                });
                var $editor = $(this).find('.editor');
                var $popups = $(this).find('.popup');
                var $toggles = $(this).find('.toggle');
                var $holders = $(this).find('.holder');
                var $toggle_smileys = $(this).find('.toggle_smileys');
                var $toggle_goods = $(this).find('.toggle_goods');
                var $toggle_topic = $(this).find('.toggle_topic');
                var $toggle_picture = $(this).find('.toggle_picture');
                var $submit = $(this).find('#twitter_editor_submit_new');
                var $input = $goods_publisher.find('input.goods_url');
                var $temp = $input;
                var show_message = function(text) {
                    $message.html(text);
                    var offset = $editor.offset();
                    $message.css({
                        top: offset.top,
                        left: offset.left + $editor.width() - $message.width() - 3
                    }).show();
                };
                var hide_message = function() {
                    $message.hide();
                };
                var getByteLen = function(val) {
                    var len = 0;
                    for (var i = 0; i < val.length; i++) {
                        var char = val.substr(i, 1);
                        if (char.match(/[^\x00-\xff]/ig) != null) len += 2;
                        else len += 1;
                    }
                    return len;
                };
                var getByteVal = function(val, max) {
                    var returnValue = '';
                    var byteValLen = 0;
                    for (var i = 0; i < val.length; i++) {
                        var char = val.substr(i, 1);
                        if (char.match(/[^\x00-\xff]/ig) != null) byteValLen += 2;
                        else byteValLen += 1;
                        if (byteValLen > max) break;
                        returnValue += char;
                    }
                    return returnValue;
                };
                var limit = 280;
                var count = function() {
                    var content = $(this).val();
                    if (getByteLen(content) >= limit - 20 * 2) {
                        var diff = Math.ceil((limit - getByteLen(content)) / 2);
                        show_message(diff >= 0 ? diff: 0);
                    } else {
                        hide_message();
                    }
                    if (getByteLen(content) > limit) {
                        $(this).val(getByteVal(content, limit));
                    }
                };
                $editor.keydown(count).focus(count).blur(count);
                $editor.click(function() {
                    $popups.hide();
                    $toggles.removeClass('active');
                });
                var offset = $toggle_smileys.offset();
                $smileys.css({
                    top: offset.top + $toggle_smileys.height(),
                    left: offset.left
                });
                $smileys.find('.smiley').click(function() {
                    $editor.insertAtCaret('[' + $(this).attr('title') + ']');
                    var len = $editor.val().length;
                    $editor.selectRange(len, len);
                });

                $toggle_smileys.click(function() {
                    var acl = options.acl_check();
                    var offset = $toggle_smileys.offset();
                    $smileys.css({
                        top: offset.top + $toggle_smileys.height(),
                        left: offset.left
                    });
                    if (!acl.status) {
                        if ('undefined' != typeof acl.message) {
                            show_message(acl.message);
                        }
                        return false;
                    }
                    $popups.not('.smileys').hide();
                    $toggles.not(this).removeClass('active');
                    $toggle_smileys.toggleClass('active');
                    $smileys.toggle();
                    $editor.focus();
                    return false;
                });
                var offset = $toggle_goods.offset();
                $goods_publisher.css({
                    top: offset.top + $toggle_goods.height(),
                    left: offset.left
                });
                $goods_publisher.find('.submit').click(function() {
                    var acl = options.acl_check();
                    if (!acl.status) {
                        if ('undefined' != typeof acl.message) {
                            show_message(acl.message);
                        }
                        return false;
                    }
                    var goods_url = $goods_publisher.find('.goods_url').val();
                    if (goods_url.substring(0, 4) != 'http') {
                        goods_url = 'http://' + goods_url;
                    }
                    if ($.isUrl(goods_url)) {
                        var $input = $goods_publisher.find('input.goods_url');
                        var $submit = $(this);
                        $input.replaceWith($goods_progress_bar);
                        $submit.hide();
                        $goods_progress_bar.show();
                        var url = '/editor/ajax_fetch_goods';
                        var data = {
                            'goods_url': goods_url,
                            'ajax': 1
                        };
                        var callback = function(response) {
                            if (response == 2) {
                                $blackShop = $.dialog({
                                    title: '<span style="margin-left: 5px;">封店提示</span>',
                                    content: $('#black_shop').show(),
                                    closeHandle: function() {
                                        $(this).closest('.dialog').hide();
                                        hideShadow();
                                    }
                                });
                                var ua = navigator.userAgent.toLowerCase();
                                var isIE6 = ua.indexOf("msie 6") > -1;
                                var fixed = 'fixed'
                                if (isIE6) {
                                    fixed = '';
                                }
                                $toggle_goods.removeClass('active');
                                show_message("");
                                $blackShop.toCenter(fixed).show();
                                showShadow();
                                return false;
                            } else {
                                response = $.JSON.decode(response);
                                if (response.status) {
                                    hide_message();
                                    $goods_holder.find('.goods_pic').attr('src', response.goods.image);
                                    $goods_holder.find('.goods_title').html(response.goods.title);
                                    $goods_holder.find('.goods_price').html(response.goods.price);
                                    $goods_publisher.hide();
                                    goods = response.goods;
                                    goods.url = goods_url;
                                    $goods_holder.show();
                                    $toggle_goods.removeClass('active');
                                    $editor.focus();
                                } else {
                                    if (typeof response.error != 'undefined') {
                                        show_message(response.error);
                                    }
                                }
                                $goods_progress_bar.replaceWith($temp);
                                $submit.show();
                            }
                        };
                        show_message('正在获取宝贝信息');
                        $.post(url, data, callback);
                    } else {
                        show_message('宝贝链接好像不对哦，换一个试试看');
                    }
                    return false;
                });
                $("#close_black").click(function() {
                    $blackShop.hide();
                    hideShadow();
                    var $submit = $goods_publisher.find('input.notloveit');
                    $goods_progress_bar.replaceWith($temp);
                    $submit.show();
                    $temp.val("");
                });
                $toggle_goods.click(function() {
                    var acl = options.acl_check();
                    var offset = $toggle_goods.offset();
                    $goods_publisher.css({
                        top: offset.top + $toggle_goods.height(),
                        left: offset.left
                    });
                    if (!acl.status) {
                        if ('undefined' != typeof acl.message) {
                            show_message(acl.message);
                        }
                        return false;
                    }
                    $popups.not('.goods_publisher').hide();
                    $toggles.not(this).removeClass('active');
                    $toggle_goods.toggleClass('active');
                    $goods_publisher.toggle();
                    if ($goods_publisher.is(':visible')) {
                        $goods_publisher.find('.goods_url').focus();
                    } else {
                        $editor.focus();
                    }
                    return false;
                });
                $toggle_topic.click(function() {
                    var acl = options.acl_check();
                    if (!acl.status) {
                        if ('undefined' != typeof acl.message) {
                            show_message(acl.message);
                        }
                        return false;
                    }
                    $popups.hide();
                    $toggles.not(this).removeClass('active');
                    var default_text = '#输入话题标题# ';
                    var v = $editor.val();
                    var found = v.search(default_text);
                    if ( - 1 == found) {
                        $editor.insertAtCaret('#输入话题标题# ');
                        v = $editor.val();
                        found = v.search(default_text);
                    }
                    $editor.selectRange(found + 1, found + 7);
                    return false;
                });
                var $upload_form = $picture_upload.find('form');
                var $upload_handle = $picture_upload.find('.upload_file');
                var offset = $toggle_picture.offset();
                $upload_handle.css({
                    'position': 'absolute',
                    'width': 80,
                    'height': 20,
                    'top': offset.top + 2,
                    'left': offset.left - 10,
                    'z-index': 9999,
                    'opacity': 0,
                    'cursor': 'pointer'
                }).hover(function() {
                    $toggle_picture.addClass('hover');
                },
                function() {
                    $toggle_picture.removeClass('hover');
                });
                var upload_func = function() {
                    var acl = options.acl_check();
                    if (!acl.status) {
                        if ('undefined' != typeof acl.message) {
                            show_message(acl.message);
                        }
                        return false;
                    }
                    $popups.hide();
                    $toggles.not($toggle_picture).removeClass('active');
                    var filename = $(this).val();
                    if (!/\.(gif|jpg|png|jpeg|bmp)$/i.test(filename)) {
                        $(this).val();
                        show_message('请上传标准图片文件, 我们支持gif,jpg,png和jpeg.');
                        $editor.focus();
                        return false;
                    }
                    $upload_picture_holder.find('img').attr('src', '');
                    $upload_picture_holder.hide();
                    $upload_form.submit();
                    $editor.focus();
                    show_message('正在上传图片');
                };
                $upload_handle.change(upload_func);
                window.upload_callback = function(response) {
                    hide_message();
                    if (response.status) {
                        $upload_picture_holder.find('img').attr('src', response.pic_url);
                        $upload_picture_holder.show();
                        twitter_pic_id = response.pic_id;
                    } else {
                        show_message(response.error);
                    }
                };
                var in_submit = false;
                $submit.click(function() {
                    var acl = options.acl_check();
                    if (!acl.status) {
                        if ('undefined' != typeof acl.message) {
                            show_message(acl.message);
                        }
                        return false;
                    }
                    if (in_submit) {
                        return false;
                    }
                    var content = $.trim($editor.val());
                    if (!content.length) {
                        show_message("说两句吧~~~");
                        return false;
                    }
                    in_submit = true;
                    show_message('正在提交');
                    $editor.attr('readonly', 'readonly').addClass('readonly');
                    var callback = function(response) {
                        $editor.val('');
                        $popups.hide();
                        $holders.hide();
                        $toggles.removeClass('active');
                        twitter_pic_id = false;
                        goods = false;
                        if (typeof Group != 'undefined' && options.submit_callback) {
                            options.submit_callback();
                        }
                        hide_message();
                        $editor.removeAttr('readonly').removeClass('readonly');
                        in_submit = false;
                        window.location.reload();
                    };
                    if (false !== goods && false !== twitter_pic_id) {
                        var url = '/goods/ajax_createGoods';
                        var data = {
                            catalog_name: goods.catalog_name,
                            gNote: content,
                            gPicID: twitter_pic_id,
                            gPicUrl: goods.image,
                            gPrice: goods.price,
                            gSouceType: 1,
                            gTitle: goods.title,
                            gUrl: goods.url,
                            goodsID: 0,
                            hasEntityPic: true,
                            isReport: true,
                            ptype: 2,
                            tag: ''
                        };
                        $.post(url, data, callback);
                    } else if (false !== goods) {
                        var url = '/goods/ajax_createGoods';
                        var data = {
                            catalog_name: goods.catalog_name,
                            gNote: content,
                            gPicID: twitter_pic_id,
                            gPicUrl: goods.image,
                            picUrls: goods.picUrls,
                            gPrice: goods.price,
                            gTitle: goods.title,
                            gUrl: goods.url,
                            gSouceType: 1,
                            goodsID: 0,
                            hasEntityPic: false,
                            isReport: false,
                            ptype: 0,
                            tag: ''
                        };
                        $.post(url, data, callback);
                    } else {
                        var url = '/twitter/ajax_newTwitter';
                        var data = {
                            goodsID: 0,
                            isLomo: false,
                            isReport: false,
                            lomoType: '',
                            pid: twitter_pic_id,
                            ptype: 0,
                            stid: 0,
                            suid: 0,
                            tContent: content,
                            tag: '',
                            type: 2
                        };
                        $.post(url, data, callback);
                    }
                    return false;
                });
            });
        }
    });
})(jQuery); (function($) {
    $(function() {
        $('#twitter_editor_new').editorize();
    });
})(jQuery);