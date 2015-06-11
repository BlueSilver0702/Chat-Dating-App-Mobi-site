/**
 * Global
 */
(function($) {

    // clear modal after loading remote data
    $("body").on("hidden.bs.modal", ".modal", function () {
        $(this).removeData("bs.modal");
    });

    function pageLoading() {
        var $container = $(".pins");
        var $img = $container.find("img[data-src]");
        var $loader = $(".page-loader");

        $img.each(function(i) {
            $(this)
                .attr("src", $(this).data("src"))
                .removeData("src")
                .css({"opacity": 0}).delay(200 * i).animate({"opacity": 1}, 200, function() {
                    $loader.delay($img.length * 200).fadeOut(200);
                })
            ;
        });
    }

    $(window).on("load",function () {
        pageLoading();
    });

})(jQuery);

/**
 * Radar
 */
//(function($) {
//
//    $(document).on("click", ".js-radar-distance a", function (e) {
//        navigator.geolocation.getCurrentPosition(function callback(position) {
//            location.href = $(e.target).attr("href")
//                + "&latitude=" + position.coords.latitude
//                + "&longitude=" + position.coords.longitude;
//        });
//        e.preventDefault();
//    });
//
//})(jQuery);

/**
 * Settings
 */
(function($) {

    $(document.body).scrollspy({target: ".settings-sidebar"});

    $(window).on("load",function () {
        $(document.body).scrollspy("refresh")
    });

    // show/hide password cahnge fields
    $(document).on("click", ".js-password-change", function (e) {
        $(this).remove();
        $(".js-password-fields").show();
        e.preventDefault();
    });

})(jQuery);

(function($) {
    if ($('.chat_add')) {
        $('.chat-add').click(function(){
            var chats = [];
                var chat = '';
                chat = $.tmpl('chatTmpl', $.extend(chat, {
                    msg_content: $('#btn-input').val()
                }));

                chats.push(chat);

            apiURL = $(".chat_add").data("url");
            $.ajax({
                url: apiURL,
                data: {text: $('#btn-input').val(), token: ChatApp.userToken}, // Page parameter to make sure we load new data
                success: function(data){
                    if (data.success) {
                        $('ul.chat').prepend(chats);
                        $('#btn-input').val('');
                    }
                }
            });
        });
    }

    $.ajax({
        url: $(".msg_alert").data("url"),
        data: {token: ChatApp.userToken}, // Page parameter to make sure we load new data
        success: function(data){
            if (data.success) {
                var count = 0;
                for ( var i = 0; i < data.data.chats.result.length; i ++) {
                    var chat = data.data.chats.result[i];
                    for (var j = 0; j < chat.participants.length; j ++) {
                        var part_one = chat.participants[j];
                        if (part_one.username != ChatApp.userUsername && part_one.unread > 0) count++;
                    }
                }
                str ='<span class="badge badge-notification" id="messageCount">'+count+'</span>';
                $("#mail-alert .icon").html(str);
            }
        }
    });

})(jQuery);

if (typeof is_home_page !== 'undefined') {
/**
 * Load moments moments
 *
 * @see \ChatApp\Controller\Api\MomentsController:searchAction
 */
(function($) {

    var handler = null,
        page = 1,
        isLoading = false,
        apiURL = $(".js-loadmore-moments").data("url");

    // Prepare layout options.
    var options = {
        autoResize: true, // This will auto-update the layout when the browser window is resized.
        container: $('#tiles'), // Optional, used for some extra CSS styling
        offset: 2, // Optional, the distance between grid items
        itemWidth: 210 // Optional, the width of a grid item
    };

    /**
     * When scrolled all the way to the bottom, add more tiles.
     */
    function onScroll(event) {
        // Only check when we're not still waiting for data.
        if(!isLoading) {
            console.log(($('.orange').scrollTop() + $('.orange').height()) +"::"+ ($('.chat-content-inner').height() - 100));
            // Check if we're within 100 pixels of the bottom edge of the broser window.
            var closeToBottom = ($('.orange').scrollTop() + $('.orange').height() > $('.chat-content-inner').height() - 100 && $('.chat-content-inner').height() - 100 > 0);
            if(closeToBottom) {

                loadData();
            }
        }
    };

    /**
     * Refreshes the layout.
     */
    function applyLayout() {
        options.container.imagesLoaded(function() {
            // Create a new layout handler when images have loaded.
            handler = $('#tiles li');
            handler.wookmark(options);
        });
    };

    /**
     * Loads data from the API.
     */
    function loadData() {
        isLoading = true;
        $('#loaderCircle').show();

        if (typeof ChatApp !== 'undefined') {
            $.ajax({
                url: apiURL,
                data: {page: page, token: ChatApp.userToken}, // Page parameter to make sure we load new data
                success: onLoadData
            });
        } else {
            $.ajax({
                url: apiURL,
                data: {page: page}, // Page parameter to make sure we load new data
                success: onLoadData
            });
        }
    };

    /**
     * Receives data from the API, creates HTML for images and updates the layout
     */
    function onLoadData(json) {
        isLoading = false;
        $('#loaderCircle').hide();

        // Increment page index for future calls.
        page++;

        //if (json.data.moments.info.offset*json.data.moments.info.limit >= json.data.moments.info.count || json.data.moments.info.count <= json.data.moments.info.limit) $loadmore.remove();

        if (json.success) {
            var moments = [];
            for (var i=0; i<json.data.moments.result.length; i++) {
                var moment = json.data.moments.result[i];
                if (moment.images.length === 0) continue;
                var comments = [];
                for (var j=0; j<moment.comments.length; j++) {
                    comments.push($.tmpl("momentCommentTmpl", $.extend(moment.comments[j], {index: j})).replace(/USERNAME/g, moment.comments[j].user));
                }

                var ratio = 0;
                var width = moment.images_sizes[0].width;
                var height = moment.images_sizes[0].height;
                if (!width) width=200;
                if (!height) height=230;
                if (width > 200) {
                    ratio = 200 / width;
                    width = width * ratio;
                    height = height * ratio;
                }

                var isLike = false;
                if (typeof ChatApp !== 'undefined')
                    for (var j=0; j<moment.likes.length; j++) {
                        if (moment.likes[j].user == ChatApp.userUsername) {
                            isLike = true;
                        }
                    }

                moment = $.tmpl("momentTmpl", $.extend(moment, {
                    color: "#"+Math.floor(Math.random()*16777215).toString(16),
                    id: moment.id,
                    width: 200,
                    height: height,
                    image: moment.images[0],
                    comments: comments.join(""),
                    totalComments: moment.comments.length,
                    totalLikes: moment.likes.length,
                    isLike: isLike
                }))
                    .replace(/MID/g, moment.id)
                    .replace(/USERNAME/g, moment.username);

                moments.push(moment);
            }

            // Add image HTML to the page.
            $('#tiles').append(moments);

            // Apply layout.
            applyLayout();

        }


    };

    // Capture scroll event.
    $('.orange').bind('scroll', onScroll);


    loadData();

})(jQuery);
} else if (!is_radar_page) {
/**
 * Load moments moments
 *
 * @see \ChatApp\Controller\Api\MomentsController:searchAction
 */
(function($) {

    function loadMoments(page) {
        if ($loadmore.data("loading")) return;
        $loader.show();
        $loadmore.data("loading", true).hide();
        $.getJSON($loadmore.data("url"), {
            token: ChatApp.userToken,
            page: page
        }, function (json) {
            $loader.hide();
            if (json.data.moments.info.offset*json.data.moments.info.limit >= json.data.moments.info.count || json.data.moments.info.count <= json.data.moments.info.limit) $loadmore.remove();
            else $loadmore.data("page", page+1).data("loading", false).show();
            if (json.success) {
                var moments = [];
                for (var i=0; i<json.data.moments.result.length; i++) {
                    var moment = json.data.moments.result[i];
                    if (moment.images.length === 0) continue;
                    var comments = [];
                    for (var j=0; j<moment.comments.length; j++) {
                        comments.push($.tmpl("momentCommentTmpl", $.extend(moment.comments[j], {index: j})).replace(/USERNAME/g, moment.comments[j].user));
                    }

                    var ratio = 0;
                    var width = moment.images_sizes[0].width;
                    var height = moment.images_sizes[0].height;
                    if (width > maxWidth) {
                        ratio = maxWidth / width;
                        width = width * ratio;
                        height = height * ratio;
                    }

                    var isLike = false;
                    for (var j=0; j<moment.likes.length; j++) {
                        if (moment.likes[j].user == ChatApp.userUsername) {
                            isLike = true;
                        }
                    }

                    moment = $.tmpl("momentTmpl", $.extend(moment, {
                        color: "#"+Math.floor(Math.random()*16777215).toString(16),
                        id: moment.id,
                        width: maxWidth,
                        height: height,
                        image: moment.images[0],
                        comments: comments.join(""),
                        totalComments: moment.comments.length,
                        totalLikes: moment.likes.length,
                        isLike: isLike
                    }))
                    .replace(/MID/g, moment.id)
                    .replace(/USERNAME/g, moment.username);

                    var elem = document.createElement("div");
                    $(elem).addClass("pin").html(moment);
                    moments.push(elem);
                }

                $masonry.append(moments).masonry("appended", moments, true);

                var $img = $masonry.find("img[data-src]");
                $img.each(function(i) {
                    $(this)
                        .attr("src", $(this).data("src"))
                        .removeData("src")
                        .css({"opacity": 0}).delay(200 * i).animate({"opacity": 1}, 200, function() {
                            $loader.delay($img.length * 200).fadeOut(200);
                        })
                    ;
                });

                // reload viewer
                View($("a.view[href]"));
            }
        });
    };

    // configure
    var maxWidth  = $("html").hasClass("mobile") ? 116 : 232;
    var $masonry  = $("#moments").masonry({
        gutter: 10,
        columnWidth: maxWidth,
        itemSelector: ".pin"
    });
    var $loader   = $(".page-loader");
    var $loadmore = $(".js-loadmore-moments");

    loadMoments(1);

    // load more when reach bottom of the page
    $(document).on("click", ".js-loadmore-moments", function (e) {
        loadMoments($(this).data("page"));
        e.preventDefault();
    });

    // show all comments
    $(document).on("click", ".js-pin-comments-showall", function (e) {
        var i=0, j=0;
        $(this).closest(".pin-comment-list").find("li").each(function () {
            i++;
            if (j<10 && $(this).css("display") == "none") {
                j++;
                $(this).show();
            }
        });
        var countHidden = parseInt($(this).find(".count").html())-j;
        if (countHidden===0) $(this).remove();
        else $(this).find(".count").html(countHidden);
        $masonry.masonry();
        e.preventDefault();
    });

    // delete moment
    $(document).on("click", ".js-moment-delete", function () {
        if (confirm("Are you sure you want to delete moment")) {
            var $this = $(this);
            $.getJSON($this.data("url"), {
                token: ChatApp.userToken
            }, function (json) {
                $this.closest(".pin").remove();
                $masonry.masonry();
            });
        }
        return false;
    });

    // delete moment
    $(document).on("click", ".js-moment-block", function () {
        if (confirm("Are you sure you want to delete moment?")) {
            var $this = $(this);
            $.getJSON($this.data("url"), {
                token: ChatApp.userToken
            }, function (json) {
                alert("Thanks for your report!\nWe'll take a look at this moment and delete it if it goes against our Terms of Service.");
                $this.closest(".pin").remove();
                $masonry.masonry();
            });
        }
        return false;
    });

})(jQuery);
} else {
    /**
     * Load radar profiles
     *
     * @see \ChatApp\Controller\Api\ContactsController:searchAction
     */
    (function($) {

        function loadContacts(page) {
            if ($loadmore.data("loading")) return;
            $loader.show();
            $loadmore.data("loading", true).hide();
            $.getJSON($loadmore.data("url"), {
                token: ChatApp.userToken,
                page: page
            }, function (json) {
                $loader.hide();
                if (json.data.contacts.info.offset*json.data.contacts.info.limit >= json.data.contacts.info.count || json.data.contacts.info.count <= json.data.contacts.info.limit) $loadmore.remove();
                else $loadmore.data("page", page+1).data("loading", false).show();
                if (json.success) {
                    var moments = [];
                    for (var i=0; i<json.data.contacts.result.length; i++) {
                        var moment = json.data.contacts.result[i];
                        if (moment.photo == '') continue;

                        var ratio = 0;
                        var width = moment.photo_size.width;
                        var height = moment.photo_size.height;
                        if (width > maxWidth) {
                            ratio = maxWidth / width;
                            width = width * ratio;
                            height = height * ratio;
                        }

                        moment = $.tmpl("radarTmpl", $.extend(moment, {
                            color: "#"+Math.floor(Math.random()*16777215).toString(16),
                            id: moment.id,
                            image: moment.photo,
                            width: maxWidth,
                            height: height,
                            aboutme: moment.aboutme,
                            region: moment.region
                        }))
                            .replace(/MID/g, moment.id)
                            .replace(/USERNAME/g, moment.username);

                        var elem = document.createElement("div");
                        $(elem).addClass("pin").html(moment);
                        moments.push(elem);
                    }

                    $masonry.append(moments).masonry("appended", moments, true);

                    var $img = $masonry.find("img[data-src]");
                    $img.each(function(i) {
                        $(this)
                            .attr("src", $(this).data("src"))
                            .removeData("src")
                            .css({"opacity": 0}).delay(200 * i).animate({"opacity": 1}, 200, function() {
                                $loader.delay($img.length * 200).fadeOut(200);
                            })
                        ;
                    });

                    // reload viewer
                    View($("a.view[href]"));
                }
            });
        };

        // configure
        var maxWidth  = $("html").hasClass("mobile") ? 116 : 232;
        var $masonry  = $("#radar").masonry({
            gutter: 10,
            columnWidth: maxWidth,
            itemSelector: ".pin"
        });
        var $loader   = $(".page-loader");
        var $loadmore = $(".js-loadmore-moments");

        loadContacts(1);

        // load more when reach bottom of the page
        $(document).on("click", ".js-loadmore-moments", function (e) {
            loadContacts($(this).data("page"));
            e.preventDefault();
        });

        // show all comments
        $(document).on("click", ".js-pin-comments-showall", function (e) {
            var i=0, j=0;
            $(this).closest(".pin-comment-list").find("li").each(function () {
                i++;
                if (j<10 && $(this).css("display") == "none") {
                    j++;
                    $(this).show();
                }
            });
            var countHidden = parseInt($(this).find(".count").html())-j;
            if (countHidden===0) $(this).remove();
            else $(this).find(".count").html(countHidden);
            $masonry.masonry();
            e.preventDefault();
        });

    })(jQuery);
}
/**
 * Like/Unlike moments
 *
 * @see \ChatApp\Controller\Api\MomentsController:likeAction
 * @see \ChatApp\Controller\Api\MomentsController:unlikeAction
 */
(function($) {

    $(document).on("click", ".js-moment-like", function () {
        var $this = $(this);
        url = $this.data("is-like") ? $this.data("unlike-url") : $this.data("like-url");

        $.getJSON(url, {
            token: ChatApp.userToken
        }, function (json) {
            var totalLikes = $this.closest(".pin-wrapper").find(".pin-meta .like .pin-social-meta-count");
            if ($this.data("is-like")) {
                $this.data("is-like", false);
                $this.html('<em class="icon icon-heart-empty"></em>');
                totalLikes.html(parseInt(totalLikes.html())-1);
            } else {
                $this.data("is-like", true);
                $this.html('<em class="icon icon-heart"></em>');
                totalLikes.html(parseInt(totalLikes.html())+1);
            }
        });

        return false;
    });

})(jQuery);

/**
 * Add moment comment
 *
 * @see \ChatApp\Controller\Api\MomentsController:addCommentAction
 */
(function($) {

    $(document).on("submit", ".js-form-moment-comments", function () {
        var $this = $(this);
        $.getJSON($this.attr("action"), $.extend($.serializeObject($this), {
            token: ChatApp.userToken
        }), function (json) {
            $this.find("textarea[name=comment]").val("");

            var totalComments = $this.closest(".pin-wrapper").find(".pin-meta .comments .pin-social-meta-count");
            totalComments.html(parseInt(totalComments.html())+1);

            var comments = $this.closest(".comments").find("ul");

            comments.prepend($.tmpl("momentCommentTmpl", $.extend(json.comment, {index: 0})).replace(/USERNAME/g, json.comment.user));

        });
        return false;
    });

})(jQuery);

/**
 * Favorite/Unfavorite users
 *
 * @see \ChatApp\Controller\Api\ContactsController:favoriteAction
 * @see \ChatApp\Controller\Api\ContactsController:unfavoriteAction
 */
(function($) {

    $(document).on("click", ".js-contact-favorite", function () {
        var $this = $(this);
        url = $this.data("is-favorite") ? $this.data("unfavorite-url") : $this.data("favorite-url");

        $.getJSON(url, {
            token: ChatApp.userToken
        }, function (json) {
            if ($this.data("is-favorite")) {
                $this.closest("is-favorite", false);
                $this.html("Follow");
            } else {
                $this.data("is-favorite", true);
                $this.html("Unfollow");
            }
        });

        return false;
    });

})(jQuery);

/**
 * Report/Delete profile
 *
 * @see \ChatApp\Controller\Api\ProfileController:deleteAction
 * @see \ChatApp\Controller\Api\ProfileController:reportAction
 */
(function($) {

    $(document).on("click", ".js-profile-delete,.js-profile-report", function () {
        if (confirm("Are you sure you want to " + ($(this).hasClass("js-profile-report") ? "report" : "delete") + " profile?")) {
            $.post($(this).data("url"), {
                token: ChatApp.userToken,
                username: $(this).data("username")
            }, function (json) {
                location.href = ChatApp.baseUrl;
            }, 'json');
        }
        return false;
    });

})(jQuery);

/**
 * Reset profile photo
 *
 * @see \ChatApp\Controller\Api\ProfileController:resetPhotoAction
 */
(function($) {

    $(document).on("click", ".js-profile-reset-photo,.js-profile-reset-background", function () {
        if (confirm("Are you sure you want to reset profile " + ($(this).hasClass("js-profile-reset-background") ? "background" : "photo") + "?")) {
            $.getJSON($(this).data("url"), {
                token: ChatApp.userToken
            }, function (json) {
                location.reload();
            });
        }
        return false;
    });

})(jQuery);
