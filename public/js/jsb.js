/*!
 * paramana.com
 * Version: 2.0
 * Started: 26-09-2010
 * Updated: 13-11-2010
 * Url    : http://www.paramana.com
 * Author : giannis (giannis AT paramana DOT com)
 *
 * Copyright (c) 2010 paramana.com
 *
 */

/*
 * Namespace the application
 * global Object in order to reference our app
 * it is passed as an argument in our
 * self calling function
 *
 */
//var jsbApp = {};
/*
 * Self calling function for our app
 *
 */
(function(jsbApp){
    //catch erros if we are not localy
    if (location.protocol != "file:" && location.host != 'localhost') {
		window.onerror = function(message, file, line){
            jsbApp.hashHandling.setHash('404');
			return true;
		}
	}
    else {
        //if we are locally create log
        // usage: log('inside coolFunc',this,arguments);
        window.log = function(){
            log.history = log.history || [];   // store logs to an array for reference
            log.history.push(arguments);
            if(this.console){
                console.log( Array.prototype.slice.call(arguments) );
            }
        };
    }
    
    /*
     * log error if we run localy
     *
     * @param {mixed} error the error to display
     */
    jsbApp.error = function(error){
        if (location.protocol == "file:" || location.host == 'localhost') {
            log(error);
        }
    }
    
    /*
     *  Template engine
     *  Initial code taken from jQuery template engine
     *
     */
    jsbApp.iTmpl = {
        /*
         * Outputs the markup based on the specified template
         *
         * @param {string} page the page we want to display
         * @param {string} the template id that we want to use for this page
         *
         * @return {string} the markup
         */
        output: function(page, tempid){
            var gVars = jsbApp.globalVars,
                lang  = (jsbApp.defaultLang != gVars.lang) ? gVars.lang + '/' : '';
            return jsbApp.iTmpl.decode(
                        gVars.templates[tempid].funcs(jQuery,
                                    gVars.contentData[gVars.lang].pages[page])
                                        .join(''))
                      .replace( /href="(?!\#)(?!mailto:)(?!ftp:\/\/)(?!http:\/\/)(?!https:\/\/)(?:\/)*([^ <>'"{}|\\^`[\]]*)"/g, 'href="#' + lang + '$1"')//is used to wrap links with a hash
                      .replace(/href="((?:ftp:\/\/|http:\/\/|https:\/\/)[^ <>'"{}|\\^`[\]]*)"/g, 'target="_blank" href="$1"')
                      .replace( /href="(#*(\w\w\/)*index|\/)(\.html|\.php)*"/g, 'href="#' + lang + 'home"' )
                      .replace( /href="#(\w\w\/)*([^ <>'"{}|\\^`[\]]*)(\.html|\.php)"/g, 'href="#' + lang + '$2"');
        },
        uiLang: {},
        loadUiLang: function(callback){
            var gVars = jsbApp.globalVars,
                _self = this;
                
            if(_self.uiLang[gVars.lang]) {
                if(callback)
                    callback();
                return;
            }
            
            $.ajax({
                url      : "get.php",
                data     : 'section=get_uilang&lang=' + gVars.lang,
                dataType : 'json',
                error: function(){},
                success: function(data) {
                    _self.uiLang[gVars.lang] = data;
                    if(callback)
                        callback();
                }
            });
        },
        /*
         * Initiates the template engine and creates the js for each template
         *
         * @param {string}   currentPage the page that is requested so to take priority
         * @param {function} callback what to call on done
         *
         */
        initiator: function(currentPage, callback){
            var _self = this;
            if(this.jsReady) {

                if(callback)
                    callback();
                return;
            }

            if(typeof jsbApp.admin != 'undefined' && typeof jsbApp.admin != null && jsbApp.admin.canEdit) {
                this.adminUser = true;
                jsbApp.admin.initiator();
            }

            this.loadUiLang(function(){
                //if a specific page requested then do this on first
                if(currentPage) {
                    _self.buildJsFromTemp(currentPage);
                    if(callback)
                        callback();
                }

                for(var i in jsbApp.globalVars.templates) {
                    _self.buildJsFromTemp(i);
                }
                _self.jsReady = true;
            });
        },
        tags: {
            "wrap": {
				_default: {$2: "null"},
				open: "$item.calls(_,$1,$2);_=[];",
				close: "call=$item.calls();_=call._.concat($item.wrap(call,_));"
			},
//            'forin': {
//                _default: {$2: "$index, $value"},
//                open  : 'if($notnull_1){for(var $2 in $1a) {$data=$1a[$2];',
//                close : '}$data=$item;}'
//            },
			"if": {
				open: "if(($notnull_1) && $1a){",
				close: "}"
			},
            'elseif': {
                open  : 'else if(($notnull_1) && $1a){',
                close : '}'
            },
			"else": {
				_default: {$1: "true"},
				open: "}else if(($notnull_1) && $1a){"
			},
			"html": {
				// Unecoded expression evaluation.
				open: "if($notnull_1){_.push($1a);}"
			},
            "@": {
				// Encoded expression evaluation. Abbreviated form is ${}.
				_default: {$1: "$data"},
				open: "if($notnull_1){var foo = iTmpl.encode($1a);_.push(foo);jsbApp.globalVars.ajaxTemp['$tempid'].push(foo);}"
			},
			"!": {
				// Comment tag. Skipped by parser
				open: ""
			},
            "each": {
				_default: {$2: "$index, $value"},
				open: "if($notnull_1){$.each($1a,function($2){$data=$value;",
                close: "});$data=$item;}"
			},
            "root": {
				// Encoded expression evaluation. Abbreviated form is ${}.
				open: "(function(){$data=$item;if($notnull_1){_.push(iTmpl.encode($1a));}})();"
			},
            "output": {
				// Encoded expression evaluation. Abbreviated form is ${}.
				open: "if('$code') {_.push('$code');}"
			},
			"=": {
				// Encoded expression evaluation. Abbreviated form is ${}.
				_default: {$1: "$data"},
				open: "if($notnull_1){_.push(iTmpl.encode($1a));}"
			},
			"ui": {
				// Encoded expression evaluation. Abbreviated form is ${}.
				open: "var foodata=$data;$data=iTmpl.uiLang[iTmpl.lang];if($notnull_1){_.push(iTmpl.encode($1a));}$data=foodata;foodata='';"
			}
        },
        /*
         * Creates the javascript for the template specified
         *
         * @param {string} tempid
         * @return {function} the js function for the template requested
         */
        buildJsFromTemp: function(tempId){
            var _self = this,
                gVars  = jsbApp.globalVars,
                temp   = gVars.templates[tempId],
                gObj   = 'jsbApp',
                markup = '';
            if(!gVars.templates[tempId]) {
                jsbApp.hashHandling.setHash('404');
                return;
            }
            if(gVars.templates[tempId].funcs && typeof gVars.templates[tempId].funcs === "function")
                return gVars.templates[tempId].funcs;

            markup = temp.markup
                         .replace( /^\{(.*)\}$/, function(all, part){
                            gVars.ajaxTemp[tempId] = [];
                            return part.split('%=').join('%@');
                         });

//            if(markup.match(/^\{.*\}$/)) {
//                markup.replace(/%= /g, '%== ')
//                      .replace(/^\{(.*)\}$/, "$1");
//            }
            return gVars.templates[tempId].funcs = new Function("jQuery","$item",
                "var $=jQuery,call,_=[],$data=$item;var iTmpl = jsbApp.iTmpl;" +

                // Introduce the data as local variables using with(){}
                "_.push('" +

                // Convert the template into pure JavaScript
                markup
                    //.replace(/%(\/?)(\w+|.)(?:\(+(.*?)?\))?\s*%/g, function(all, slash, type, target){
                    .replace(/%(\/?)(\w+|.)(?:\(((?:[^\%]|\%(?!\%))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\%]|\%(?!\%))*?)\))?\s*%/g,
                        function( all, slash, type, fnargs, target, parens, args ) {
                            var tag     = _self.tags[type],
                                expr    = '',
                                widgetF = '',
                                exprAutoFnDetect = '',
                                def;

                            if ( !tag ) {
                                throw "Wrong symbol: " + all + ', maybe a misssng %= on temp ' + tempId;
                            }
                            
                            if (target && target.indexOf('ui_') > -1)
                                tag = _self.tags.ui;


                            def = tag._default || [];
                            if ( parens && !/\w$/.test(target)) {
                                target += parens;
                                parens = "";
                            }

                            if (fnargs && fnargs.charAt(0) == '[' && fnargs.charAt(fnargs.length - 1)) {
                                target = fnargs.substring(1, fnargs.length-1)
                                fnargs = '';
                            }

                            if(type == 'output') {
                                if(fnargs)
                                    target = fnargs.split('"').join('').split("'").join('');
                                else
                                    target = target.split('"').join('').split("'").join('');
                                fnargs = '';
                            }

                            //case we have a target ex: each(target)
                            if ( target ) {
                                target = jsbApp.iTmpl.unescape( target );
                                target = target.split('/').join('.');
                                if(target.indexOf('_widget') > -1) {
                                    var widgetName = target.split('_')[0];
                                    //if(!gVars.templates[widgetName].funcs || typeof gVars.templates[widgetName].funcs === undefined || typeof gVars.templates[widgetName].funcs === null)
                                    gVars.templates[widgetName].funcs = _self.buildJsFromTemp(widgetName);
                                    widgetF = "_.push(jsbApp.globalVars.templates['" + widgetName + "'].funcs(jQuery, this).join(''))";
                                }
                                else {
                                    args = args ? ("," + jsbApp.iTmpl.unescape( args ) + ")") : (parens ? ")" : "");
                                    // Support for target being things like a.toLowerCase();
                                    // In that case don't call with template item as 'this' pointer. Just evaluate...
                                    expr = parens ? (target.indexOf(".") > -1 && target.indexOf(gObj) == -1 ? target + parens : ("(" + target + ").call($data" + args)) : '$data.' + target;
                                    exprAutoFnDetect = parens ? expr : "(typeof(" + target.split('.')[0].replace(/\[[\w\d\]]+/, '') + ")!== 'undefined' && typeof(" + target + ")==='function'?(" + target + ").call($item):($data." + target + "))";
                                }
                            }
                            else {
                                exprAutoFnDetect = expr = def.$1 || "null";
                            }
                            fnargs = unescape( fnargs );

                            return "');" +
                                widgetF  + ';' +
                                tag[ slash ? "close" : "open" ]
                                    .split( "$notnull_1" ).join( target && !parens ? "typeof($data." + target.split('.')[0] + ")!=='undefined' && ($data." + target.split('.')[0] + ")!=null" : "true" )
                                    .split( "$1a" ).join( exprAutoFnDetect )
                                    .split( "$1" ).join( expr )
                                    .split( "$tempid" ).join(tempId)
                                    .split( "$code" ).join(target)
                                    .split("$data.$index").join('$index')
                                    .split("$data.$value").join('$value')
                                    .split("$data.this").join('$data')
                                    .split( "$2" ).join( fnargs ?
                                        fnargs.replace( /\s*([^\(]+)\s*(\((.*?)\))?/g, function( all, name, parens, params ) {
                                            params = params ? ("," + params + ")") : (parens ? ")" : "");
                                            return params ? ("(" + name + ").call($item" + params) : all;
                                        })
                                        : (def.$2||"")
                                    ) +
                                "_.push('";
                        }) +
                    "');return _;"
            );
        },
        /*
         * Encodes a text escapes <>"'
         *
         * @param {string} text
         */
        encode: function( text ) {
			// Do HTML encoding replacing < > & and ' and " by corresponding entities.
			return !text ? ''
                         : ("" + text).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;");
		},
        /*
         * Decodes a text unescapes <>"'
         *
         * @param {string} text
         */
        decode: function( text) {
            return !text ? '' : ("" + text).split("&lt;").join("<").split("&gt;").join(">").split('&#34;').join('"').split("&#39;").join("'");
        },
        unescape: function( args ) {
            return args ? args.replace( /\\'/g, "'").replace(/\\\\/g, "\\" ) : null;
        }
    }

    /*
     * the page loader
     */
    jsbApp.pageloader = {
        start: function(){
            if(this.$running)
                return;

            this.$running      = true;
            this.$loadingFrame = 1;
            var _self       = this,
            loader = document.getElementById('pageLoading'),
            oSpinner    = $('div', loader)[0];

            loader.style.display = 'block';
            this.$timer = setInterval(function(){
                oSpinner.style.top = (_self.$loadingFrame * - 30) + 'px';//15 = frame height
                _self.$loadingFrame = (_self.$loadingFrame + 1) % 32; //32 = frames length
            }, 66);
        },
        stop: function(){
            clearInterval(this.$timer);

            var loader  = document.getElementById('pageLoading'),
            oSpinner = $('div', loader)[0];

            loader.style.display = 'none';

            this.$loadingFrame = 1;
            oSpinner.style.top = 0;

            this.$running = false;
        }
    }

    /*
     * the gallery in individual works
     */
    jsbApp.gallery = {
        nav: function(imgPath, $trigger){
            var _self      = this,
                $thumbItem = $trigger.closest('.thumbItem'),
                context    = $trigger.closest('.photoGallery')[0];

            imgPath = imgPath.replace(/.*#/, '').replace(jsbApp.globalVars.lang + '/', '');
            if(this.curImg == imgPath || (!this.curImg && $thumbItem.index() == 0)) {
                this.curImg = imgPath;
                return;
            }

            jsbApp.gallery.loadIndicatorStart(context);
            $('.thumbItem.active', context).removeClass('active');
            $thumbItem.addClass('active');
            $('.photoFocus img', context).stop().animate({
                'opacity': 0
            }, function(){
                var $this = $(this);
                $this.attr('src', imgPath);
                _self.curImg = imgPath;
                $this.load(function(){
                    jsbApp.gallery.loadIndicatorStop(context);
                    $this.stop().animate({
                        'opacity': 1
                    }, 'fast');
                });
            });
        },
        loadIndicatorStart: function(context){
            if(this.$running)
                return;

            this.$running      = true;
            this.$loadingFrame = 1;
            var _self       = this,
            photoLoader = $('.photoLoader', context)[0],
            oSpinner    = $('div', photoLoader)[0];

            photoLoader.style.display = 'block';
            this.$timer = setInterval(function(){
                oSpinner.style.top = (_self.$loadingFrame * - 15) + 'px';//15 = frame height
                _self.$loadingFrame = (_self.$loadingFrame + 1) % 32; //32 = frames length
            }, 66);
        },
        loadIndicatorStop: function(context){
            clearInterval(this.$timer);

            var photoLoader  = $('.photoLoader', context)[0],
            oSpinner = $('div', photoLoader)[0];

            photoLoader.style.display = 'none';

            this.$loadingFrame = 1;
            oSpinner.style.top = 0;

            this.$running = false;
        }//,
//        init: function(){
//
//        }
    }

    /*
     * the sidebar navigation in services page
     */
    jsbApp.jumperList = {
        aniimating: false,
        sections: {},
        jump: function(toEl, $trigger, noDocScroll){
            var _self        = this,
                targetOffset = $(document.getElementById(toEl)).offset().top,
                services     = document.getElementById('servicesPage'),
                $jumperList  = $('.jumperList', services);

            if(!$trigger)
                $trigger = $('.jumperList a[rel="' + toEl + '"]');

            if(!noDocScroll) {
                _self.aniimating = true;
            }

            $('.active', services).removeClass('active');
            var $triggerIndex = $trigger.closest('.serviceItem').addClass('active').index();
            $jumperList
                .addClass('normal')
                .stop()
                .animate({
                    top: targetOffset - $jumperList.outerHeight() + parseInt($jumperList.offset().top - $trigger.offset().top) - 15
                }, function(){
                    if(noDocScroll)
                        $(this).removeClass('normal');
                });
            if(!noDocScroll) {
                $('body, html').stop().animate({
                    scrollTop: $triggerIndex == 0 ? 0 : targetOffset - 95
                }, function(){
                    $jumperList.removeClass('normal');
                    _self.aniimating = false;
                });
            }
        },
        getPositions: function(){
            var _self       = this,
                $jumperList = $('.jumperList', document.getElementById('servicesPage'));

            $jumperList.find('a').each(function() {
                var $trigger  = $(this),
                    linkHref  = $trigger.attr('rel'),
                    targetTop = $('#' + linkHref).offset().top;
                _self.sections[linkHref] = Math.round(targetTop);
            });
        },
        getSection: function(){
            var _self         = this,
                $window       = $(window),
                $winScrollTop = $window.scrollTop(),
                returnValue   = {},
                windowHeight  = Math.round($window.height() / 2);

            for(var section in _self.sections) {
                if((_self.sections[section] - windowHeight) < $winScrollTop - ($winScrollTop + $window.height() > $(document).height() -20 ? 0 : 200)) {
                    returnValue.elem = section;
                    returnValue.pos  = _self.sections[section];
                }
            }


            return returnValue;
        },
        scrollChange: function(){
            if(jQuery.isEmptyObject(jsbApp.jumperList.sections))
                jsbApp.jumperList.getPositions()
            var section = jsbApp.jumperList.getSection();
            if(section.elem)
                jsbApp.jumperList.jump(section.elem, null, true);
        }
    }
    /*
     * Front page carousel
     */
    jsbApp.carousel = {
        carouselInterval: '',
        carouselPage    : 0,
        pauseCarousel: function(){
            clearInterval(this.carouselInterval);
        },
        startCarousel: function(reset, create){
            var $slidingElements = $(document.getElementById('slidingElements')),
                $carouselMeter   = $(document.getElementById('carouselMeter')),
                slideNum         = $slidingElements.find('.carousel_element').length,
                step             = 940,
                meterStep        = 30,
                self             = this,
                navEls           = ['<ul>'];

            if(this.carouselInterval)
                clearInterval(this.carouselInterval);

            //create carousel nav
            if(create) {
                for(var i = 0; i < slideNum; i++) {
                    navEls.push('<li><a href="#" class="carouselCn"></a></li>');
                }
                navEls.push('</ul>');
                $carouselMeter.after(navEls.join(''));
            }

            //reset carousel
            if(reset) {
                self.carouselPage = 0;
                $carouselMeter.css('left', 0);
                $slidingElements.css('marginLeft', 0);
            }

            this.carouselInterval = setInterval(function(){
                self.carouselPage++;
                self.carouselPage = (self.carouselPage)%slideNum;

                $carouselMeter.stop().animate({
                    'left': (self.carouselPage)* meterStep
                }, self.carouselPage == 0 ? 'normal' : 'slow');

                $slidingElements.stop().animate({
                    marginLeft: -(self.carouselPage * step)
                }, self.carouselPage == 0 ? 'normal' : 'slow');
            }, 5000);
        },
        goToCarousel: function(carouselNum){
            var step        = 940,
                meterStep   = 30;

            if(this.carouselInterval)
                clearInterval(this.carouselInterval);

            this.carouselPage = carouselNum;

            $(document.getElementById('carouselMeter')).stop().animate({
                'left': this.carouselPage * meterStep
            }, 'slow');

            $(document.getElementById('slidingElements')).stop().animate({
                marginLeft: -(this.carouselPage * step)
            }, 'slow');
        },
        events: function(){
            var _self       = this,
                $carouselIn = $(document.getElementById('carouselIn'));
            $carouselIn.bind({
                mouseover: function() {
                    _self.pauseCarousel();
                },
                mouseout: function() {
                    _self.startCarousel();
                }
            })
        },
        init: function(){
            this.events();
            this.startCarousel(true, true);
        }
    }

    /*
     * Contacting related forms
     *
     */
    jsbApp.giveFeedback = {
        contactStart: false,
        contactRequire : {
            'name'   : true,
            'email'  : true,
            'phone'  : false,
            'message': true

        },
        contactForm: function(event){
            var _self   = this,
                $button = event.target.id == 'sendContact' ? $(event.target) : $(event.target).parent();

            if(!_self.contactStart) {
                // validate and process form
                // first hide any error messages
                $('.error').hide();

                //check for onoma
                var $name = $(document.getElementById('nameContact')),
                name  = $name.val();
                if (jsbApp.giveFeedback.contactRequire.name && name == "") {
                    $(document.getElementById('nameContactError')).fadeIn('slow');
                    $name.trigger('focus');
                    return false;
                }

                //check for email
                var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/,
                $email   = $(document.getElementById('emailContact')),
                email    = $email.val();
                if (jsbApp.giveFeedback.contactRequire.email && email == "") {
                    $(document.getElementById('emailContactError')).fadeIn('slow');
                    $email.trigger('focus');
                    return false;
                }
                else if (!emailReg.test(email) && !(email == "")) {
                    $(document.getElementById('emailContactWrong')).fadeIn('slow');
                    $email.trigger('focus');
                    return false;
                }

                //check for phone
                var $phone = $(document.getElementById('phoneContact')),
                phone  = $phone.val();
                if(jsbApp.giveFeedback.contactRequire.phone && $phone.length > 0 && !/^[\d\+\- \(\)]+$/.test(phone)) {
                    $(document.getElementById('phoneContactError')).fadeIn('slow');
                    $phone.trigger('focus');
                    return false;
                }

                //check for message
                var $message = $(document.getElementById('messageContact')),
                message  = $message.val();
                if (jsbApp.giveFeedback.contactRequire.message && message == "" || message.length < 8) {
                    if(message.length < 8)
                        $(document.getElementById('messageContactShort')).fadeIn('slow');
                    else
                        $(document.getElementById('messageContactError')).fadeIn('slow');
                    $message.trigger('focus');
                    return false;
                }

                var dataString = 'name='+ name + '&email=' + email + '&phone=' + phone + '&message=' + message;
                $.ajax({
                    type: "POST",
                    url: "post.php",
                    data: 'section=contactform&' + dataString,
                    beforeSend: function() {
                        _self.contactStart = true;
                        document.getElementById('disableSendContact').style.display = 'block';
                        $button.addClass('disabledBtn').find('.btnContent').html('Μισό λεπτό');//.end().width($('.btnContent', $button[0]).outerWidth() + 8);
                    },
                    success: function() {
                        jsbApp.googleTrack.trackEvent('contact', 'send', 'contactForm');
                        _self.contactStart = false;
                        document.getElementById('disableSendContact').style.display = 'none';
                        $button.removeClass('disabledBtn').find('.btnContent').html('Αποστολή');//.end().width($('.btnContent', $button[0]).outerWidth() + 8);
                    }
                });
            }
        }
    };

    /*
     * Creates the twitter feed feed
     *
     * @final
     */
    jsbApp.twitter = {
        feed: function(){
            var feedTemp = '<div class="feed">\
                                <div class="date">%date%</div>\
                                <div class="text">%tweet%</div>\
                            </div>';
            $.ajax({
                type     : "GET",
                url      : 'get.php',
                dataType : 'xml',
                data     : "section=tweetfeed",
                timeout  : 10000,
                error: function(){
                    $(document.getElementById('feeds')).remove();
                },
                success: function(data) {
                    //$('user created_at', data).remove();
                    //$('user id', data).remove();
                    var hook = ['<div>'];
                    $('status', data).each(function(i){
                        var _temp = feedTemp;
                        //_temp = _temp.replace(/%href%/, $('id:eq(0)', this).text());
                        _temp = _temp.replace(/%tweet%/, jsbApp.util.wrapLink($('text:eq(0)', this).text()));
                        _temp = _temp.replace(/%date%/, jsbApp.util.formatDate($('created_at:eq(0)', this).text().replace(/\+\d\d\d\d/, ""), 'fullgr'));
                        hook.push(_temp);
                    });
                    hook[hook.length] = '</div>';
                    $(document.getElementById('feeds')).html(hook.join(''));
                }
            });
       }
    };

    /*
     * google analytics
     *
     * @param {string} str the url to pass in the google track
     * @final
     */
    jsbApp.googleTrack = {
		init: false,
        trackEvent: function(cat, action, label){
            if(typeof(_gaq) == "object")
                _gaq.push(['gTrack._trackEvent', cat || '', action || '', label || '']);
        },
        pageView: function(str) {
            if(typeof(_gaq) == "object")
                _gaq.push(['gTrack._trackPageview', str]);
        }
	}

    jsbApp.events = function(){
        var $eventTrigger = {},
            $workItem;
        $('body').bind({
            click: function(event){
                $eventTrigger = $(event.target);//wrap the event target in a jQuery object

                if($eventTrigger.hasClass('carouselCn')) {
                    jsbApp.carousel.goToCarousel($eventTrigger.parent().index());
                    return false;
                }
                else if($eventTrigger.hasClass('serviceItemLink') || $eventTrigger.closest('.serviceItemLink').length > 0) {
                    $eventTrigger = $eventTrigger.hasClass('serviceItemLink') ? $eventTrigger : $eventTrigger.closest('.serviceItemLink');
                    jsbApp.jumperList.jump($eventTrigger[0].href.replace(/.+#/, ''), $eventTrigger);
                    return false;
                }
                else if($eventTrigger.hasClass('thumbItemLink')){
                    jsbApp.gallery.nav($eventTrigger[0].href, $eventTrigger);
                    return false;
                }
                else if(event.target.id == 'sendContact' || $eventTrigger.parent()[0].id == 'sendContact') {
                    jsbApp.giveFeedback.contactForm(event);
                    return false;
                }
                else if($workItem = $eventTrigger.closest('div.workItem')) {
                    if($workItem.length > 0 && $('.itemBlack', $workItem[0]).is(':hidden')) {
                        jsbApp.hashHandling.setHash($('.workLink', $workItem[0]).attr('href').split('#')[1]);
                        return false;
                    }
                }
            },
            keyup: function(event){
                $eventTrigger = $(event.target);//wrap the event target in a jQuery object

                if(event.target.tagName.toLowerCase() == 'input' || event.target.tagName.toLowerCase() == 'textarea')
                    $('.error', this).fadeOut('slow');
            },
            blur: function(event) {
                $eventTrigger = $(event.target);//wrap the event target in a jQuery object

                if(event.target.tagName.toLowerCase() == 'input' || event.target.tagName.toLowerCase() == 'textarea')
                    $('.error', this).fadeOut('slow');
            }
//            ,
//            mouseover: function(event) {
//                $eventTrigger = $(event.target);
//                var $workItem;
//                if($workItem = $eventTrigger.closest('div.workItem')) {
//                    $workItem.addClass('workitemOver');
//                }
//            },
//            mouseout: function(event) {
//                $eventTrigger = $(event.target);
//                var $workItem;
//                if($workItem = $eventTrigger.closest('div.workItem')) {
//                    $workItem.removeClass('workitemOver');
//                }
//            }
        });

        if(!jQuery.browser.msie || (jQuery.browser.msie && jQuery.browser.version >= 8)) {
            $(window).bind('scroll.servicesScroll', function(event){
                if(!jsbApp.jumperList.aniimating && !$(document.getElementById('servicesPage')).is(':hidden')) {
                    jsbApp.jumperList.scrollChange();
                }
            });
        }
    };

    jsbApp.goToPage = function(page, tempid, parentPage){
        var gVars = jsbApp.globalVars,//cache globalVars objs
            _self = this;
        if(!gVars.templates[tempid]) {
            jsbApp.hashHandling.setHash('404');
            return;
        }

        jsbApp.pageloader.start();

        $('.activeBtn', document.getElementById('navButs')).removeClass('activeBtn');
        if(page != '404') {
            if($(document.getElementById((parentPage || page)  + 'Btn')).length > 0)
                $(document.getElementById((parentPage || page)  + 'Btn')).addClass('activeBtn');
            else
                $(document.getElementById('homeBtn')).addClass('activeBtn');
        }
        //show loader
        if(gVars.displayedPage != page) {
            $(document.getElementById('content')).children('[id!=pageLoading]').each(function(){
                $(this)[0].style.display = 'none';
            });
        }

        $('body, html').stop().animate({
            scrollTop: 0
        });

        if(!gVars.renderedPages[page] && $('#' + page + 'Page').length <= 0) {
            var langData = gVars.contentData[gVars.lang].pages,
                cData;
            if(langData && (cData = langData[page])) {
                parseData(cData);
            }
            else{
                $.ajax({
                    url      : "get.php",
                    data     : 'section=get_page&lang=' + gVars.lang + '&id=' + page,
                    dataType : 'json',
                    beforeSend: function(){
                        gVars.renderedPages[page] = true;
                    },
                    error: function(e){
                        jsbApp.error(e);
                    },
                    success: function(data) {
                          parseData(data);
                    }
                });
            }
            return;
        }
        //just show the right page
        var $page = $(document.getElementById(page + 'Page'));
        if($page.length > 0) {
            $page.show();
            trackPage();
            gVars.displayedPage = page;
            gVars.renderedPages[page] = true;
            jsbApp.pageloader.stop();
        }

        function parseData(data){
            gVars.contentData[gVars.lang].pages[page] = data;
            trackPage();

            $(document.getElementById('content')).append(
                '<div id="' + page + 'Page">' + jsbApp.iTmpl.output(page, tempid) + '</div>'
            );

            jsbApp.pageloader.stop();
            $('#' + page + 'Page').show();

            gVars.displayedPage = page;
            gVars.renderedPages[page] = true;

            if(page == 'home') {
                jsbApp.carousel.init();
            }
        }

        function trackPage(){
            document.title = gVars.contentData[gVars.lang].pages[page].title + ' | Paramana.com';
            if(page != 'home' && !jsbApp.googleTrack.init)
                jsbApp.googleTrack.init = true;
            jsbApp.googleTrack.pageView('/' + jsbApp.hashHandling.getHash() + '.html');
        }
    };

    /*
     * Does the site navigation
     * based on the hash that is being feed
     * this function is called from the hashchange event
     *
     * @param {string} hash    the window hash
     *
     */
    jsbApp.siteNav = function(hash){
        var globalVarsLookUp = jsbApp.globalVars, //cache the globalVars for faster lookup
            _self            = this,
            tempid           = 'home',
            page             = 'home',
            subpage          = '',
            parentPage       = '',
            lang             = '',
            $langs;

        if(globalVarsLookUp.ignoreHashChange) {
            globalVarsLookUp.ignoreHashChange = false;
            return;
        }

        hash    = hash.split('/');
        page    = tempid = hash.shift();
        subpage = hash.shift();

        if(jsbApp.util.arrayIndexOf(globalVarsLookUp.availableLangs, page) > -1) {
            lang    = page;
            page    = tempid = subpage || 'home';
            subpage = hash.shift();
        }
        else
            lang = jsbApp.defaultLang;

        if(!globalVarsLookUp.inited || globalVarsLookUp.lang != lang) {
            globalVarsLookUp.lang = lang || jsbApp.defaultLang;
            jsbApp.util.fixPageLinks(jsbApp.defaultLang == lang ? '' : lang);
            jsbApp.util.cleanContent();
            jsbApp.setLangs();
            globalVarsLookUp.inited = true;
            jsbApp.iTmpl.lang = globalVarsLookUp.lang;
            jsbApp.iTmpl.loadUiLang(function(){
                process.call(_self);
            });
        }
        else {
            process();
        }

        function process(){
            if(subpage){
                tempid     = 'subpage-' + page;
                parentPage = page
                page       = subpage;
            }

            $langs = $('.lang', document.getElementById('header'));
            if($langs.length > 0) {
                $langs.each(function(){
                    var $this = $(this),
                        href  = ($this.attr('id').indexOf(jsbApp.defaultLang) > -1 ? '' : $this.attr('id').split('_')[1] + '/') + (parentPage ? parentPage + '/' : '') + page;
                    $this.attr('href', '#' + href);
                });
            }
            
            if(jsbApp.iTmpl.jsReady)
                return jsbApp.goToPage(page, tempid, parentPage);

            return jsbApp.iTmpl.initiator(tempid, function(){
                        jsbApp.goToPage(page, tempid, parentPage);
                    });
        }
    }

    jsbApp.setLangs = function(){
        var langs     = jsbApp.appProps.langs,
            $langHook = $(document.getElementById('langHook')),
            hook      = [''];

        if($langHook.length > 0) {
            for(var i in langs){
                hook.push('<a id="lang_' + i + '" ' + (jsbApp.globalVars.lang == i ? 'style="display:none;"' : '') + ' class="lang georgia italic" href="#' + (i != jsbApp.defaultLang ? i + '/' : '') + 'home' + '" title="' + langs[i].link.title + '">' + langs[i].link.text + '</a>');
            }
            $langHook.after(hook.join('')).remove();
        }
        else {
            $('.lang[id!="lang_' + jsbApp.globalVars.lang + '"]').show();
            $('.lang[id="lang_' + jsbApp.globalVars.lang + '"]').hide();
        }
        //<a class="lang georgia italic" href="" title="ελληνική έκδοση">γλώσσα Ελληνικά</a>
        //<a class="lang georgia italic" href="" title="switch to english">language English</a>
    };

    /*
     * Cleans the location path or hash
     * providing backwards compatibility
     *
     */
    jsbApp.sanitizePath = function(){
        var originalHash = jsbApp.hashHandling.getHash(),
            fooHash      = '',
            path;
        if (originalHash)
            fooHash = originalHash.replace(/\.html|\.php$/,'') || 'home';
        else {
            fooHash = window.location.pathname.replace(jsbApp.hashHandling.sitePath.replace('localhost', ''), '').replace(/\.html|\.php$/,'').replace(/^\/|\/$/g, '') || 'home';
            path = fooHash.split('/');
            path = path.shift();
            if(jsbApp.util.arrayIndexOf(jsbApp.globalVars.availableLangs, path) > -1) {
                jsbApp.globalVars.lang = path;
            }
        }

        if(originalHash != fooHash)
            jsbApp.hashHandling.setHash(fooHash);
    };

    jsbApp.webStart = {
        jsButtons : function(){
            $('.buttonEl').each(function(){
                $(this).width($('.btnContent', this).outerWidth() + 8);
            });
        },
        init: function() {
            jsbApp.util.fixPageLinks();
            if (jQuery.browser.version < 8)
                this.jsButtons();
        }

    };
    /*
     * syncronously gets the html templates
     *
     */
    jsbApp.getTemps = function(callback){
        var gVars = jsbApp.globalVars,
            temps = gVars.templates;
        $.ajax({
            url      : "temp/temp.php",
            data     : 'temp=all',
            dataType : 'text',
            error: function(){},
            success: function(data) {
                data = data.split('<!%-%-temp-%-%>');
                for(var i = 0, l = data.length; i < l; i++) {
                    data[i].replace(/<!%-%-temp=(.*)-%-%>(.*)/g, function(i, title, content){
                        var info = title.split('_'),
                            name = info[0];
                        temps[name] = {},
                        temps[name].funcs  = '';
                        temps[name].type   = info[1];
                        temps[name].markup = jQuery.trim(content)
                                                   .replace( /([\\'])/g, "\\$1" )
                                                   .replace( /[\r\t\n]/g, " " )
                                                   .replace( /('|")\.\.\//g, '$1' )
                                                   .replace( /%(\=|@)([^\%]*)%/g, "%$1 $2%" );
                    });
                }
                if(callback)
                    callback();
            }
        });
    };

    /*
     * Hash functions
     *
     */
    jsbApp.hashHandling = {
        sitePath: (window.location.host == 'localhost' ? 'localhost/paramana.com/branches/ver2/gr/public/' : window.location.host),
        setHash: function(hash, title){
            if(hash)
                window.location.hash = hash.replace(jsbApp.hashHandling.sitePath, '')
                                           .replace(/^http:\/\//, '')
                                           .replace(/\.html$|\.php$/,'');
            if (title)
                document.title = title.uCaseWords();
        },
        getHash: function(){
            return jsbApp.util.decodeUTF8(window.location.hash.replace(/^#/, ''));
        },
        getTitle: function(){
            return document.title;
        }
    }

    /*
     * Our global storage object
     *
     */
    jsbApp.globalVars = {
        renderedPages  : [],
        displayedPage  : 'home',
        templates      : {},
        lang           : jsbApp.defaultLang,
        availableLangs : [],
        ajaxTemp       : {},
        contentData    : {}
    }

    jsbApp.util = {
        toServicesTitle: function(main){
            return main ? this.title : this.title.replace('+','<br/>+');
        },
        cssbg: function(options){
            return "style=\"background:transparent url('" + this.bg + "') no-repeat " + options.pos + ";\"";
        },

        isScrolledIntoView: function(elem) {
            var docViewTop = $(window).scrollTop();
            var docViewBottom = docViewTop + $(window).height();

            var elemTop = $(elem).offset().top;
            var elemBottom = elemTop + $(elem).height();

            return ((elemBottom >= docViewTop) && (elemTop <= docViewBottom));
        },


        /*
         * Vazei ta links se ena href
         *
         * @param  {string} str
         * @return {string}
         * @final
         */
        wrapLink: function(str) {
            return str.replace(/((?:mailto:|ftp:\/\/|http:\/\/|https:\/\/)[^ <>'"{}|\\^`[\]]*)/g, '<a target="_blank" href="$1">$1</a>');
        },
        /*
         * Kanei format thn hmeromhnia
         *
         * @param  {string} str
         * @param  {string} type 3gr -> 3 characters and greek (23 Ιαν), fullgr (23 Ιανουαρίου)
         * @return {string}
         * @final
         */
        formatDate: function(str, type) {
            str = str.toLowerCase();
            str.match(/(\w\w\w).*(\w\w\w).*(\d\d).*(\d\d:\d\d:\d\d).*(\d\d\d\d)/);
            var weekDay   = RegExp.$1;
            var monthName = RegExp.$2;
            var dateNum   = RegExp.$3;
            var time      = RegExp.$4;
            var year      = RegExp.$5;
            var result;
            var month = jsbApp.monthLut[monthName];
            if(month) {
                if(type == '3gr') {
                    result = jsbApp.monthLut[monthName][0];
                }
                else if(type == 'fullgr') {
                    result = jsbApp.monthLut[monthName][1];
                }
            }
            return dateNum + ' ' + result;
        },
        /*
         * Encodes utf-8
         *
         * @param  {string} string
         * @return {string}
         * @final
         */
        encodeUTF8: function(string) {
            try {
                return encodeURI(string);
            }
            catch(err) {
                try {
                    return encodeURIComponent(string);
                }
                catch(err) {
                    return (string);
                }
            }
        },
        /*
         * Decodes utf-8
         *
         * @param  {string} string
         * @return {string}
         * @final
         */
        decodeUTF8: function(string) {
            try {
                return decodeURI(string);
            }
            catch(err) {
                try {
                    return decodeURIComponent(string);
                }
                catch(err) {
                    return (string);
                }
            }
        },
        /*
         * Elenxos an einai mono h xugo
         *
         * @return {boolean}
         * @final
         */
        isEven: function(num) {
            return (num%2) ? true : false;
        },

        /*
         * modulo function
         *
         * @param  {int} num
         * @param  {int} modulo
         * @return {boolean}
         * @final
         */
        modulo: function(num, modulo) {
            return (num%modulo) ? true : false;
        },

        /*
         * Returns the sum of the array values
         *
         * @param  {array}  array
         * @return {number} the sum
         * @final
         */
        arraySum: function(array) {
            for (var i = 0, L = array.length, sum = 0; i < L; sum += (array[i++] - 0));
            return sum;
        },

        /*
         * Search for the index of the first occurence of a value 'obj' inside an array
         * instance.
         * July 29, 2008: added 'from' argument support to indexOf()
         *
         * @param {mixed}  obj    The value to search for inside the array
         * @param {Number} [from] Left offset index to start the search from
         * @type  {Number}
         */
        arrayIndexOf: function(aray, obj, from){
            var len = aray.length;
            for (var i = (from < 0) ? Math.max(0, len + from) : from || 0; i < len; i++) {
                if (aray[i] === obj)
                    return i;
            }
            return -1;
        },

        cleanContent: function(){
            $(document.getElementById('content')).children('[id!=pageLoading]').remove();
            jsbApp.globalVars.renderedPages = [];
        },

        fixPageLinks: function(lang){
            lang = lang ? lang + '/' : (jsbApp.defaultLang != jsbApp.globalVars.lang) ? jsbApp.globalVars.lang + '/' : '';
            $('a', document.getElementById('warper')).each(function(){
                var $this    = $(this), path,
                    hasClass = $this.hasClass('internalLink'),
                    href     = (jQuery.browser.msie && jQuery.browser.version < 8)
                                ? $this.attr('href').replace('http://' + window.location.hostname + window.location.pathname, '')
                                : $this.attr('href');

                if(href && !$this.hasClass('lang')){
                        href = href.replace( /^(?!\#)^(?!mailto:)^(?!ftp:\/\/)^(?!http:\/\/)^(?!https:\/\/)(?:\/)*([^ <>'"{}|\\^`[\]]*)(\.html|\.php)/, '#$1')
                                   .replace( /^(#*(\w\w\/)*index|(\/\w\w)*\/)(\.html|\.php)*$/, '#' + lang + 'home' )
                                   .replace( /#(\w\w\/)*([^ <>'"{}|\\^`[\]]*)(\.html|\.php)/, '#' + lang + '$2');

                    if(href.match(/((?:ftp:\/\/|http:\/\/|https:\/\/)[^ <>'"{}|\\^`[\]]*)/))
                        $this.attr('target', '_blank');

                    if(hasClass) {
                        path = href.replace(/^#/,'').split('/');
                        if(jsbApp.util.arrayIndexOf(jsbApp.globalVars.availableLangs, path[0]) > -1) {
                            path.shift();
                        }
                        href = '#' + lang + path.join('/');
                    }

                    if(href.match(/^#/) && !hasClass)
                        $this.attr('href', href).addClass('internalLink');
                    else
                        $this.attr('href', href);
                }
            });
        }
    };

    jsbApp.monthLut = {
        "january"   : ["Ιαν",  "Ιανουαρίου"],
        "jan"       : ["Ιαν",  "Ιανουαρίου"],
        "february"  : ["Φεβ",  "Φεβρουαρίου"],
        "feb"       : ["Φεβ",  "Φεβρουαρίου"],
        "march"     : ["Μαρ",  "Μαρτίου"],
        "mar"       : ["Μαρ",  "Μαρτίου"],
        "april"     : ["Απρ",  "Απριλίου"],
        "apr"       : ["Απρ",  "Απριλίου"],
        "may"       : ["Μάη",  "Μαίου"],
        "june"      : ["Ιουν", "Ιουνίου"],
        "jun"       : ["Ιουν", "Ιουνίου"],
        "july"      : ["Ιουλ", "Ιουλίου"],
        "jul"       : ["Ιουλ", "Ιουλίου"],
        "august"    : ["Αυγ",  "Αυγούστου"],
        "aug"       : ["Αυγ",  "Αυγούστου"],
        "september" : ["Σεπτ", "Σεπτεμβρίου"],
        "sep"       : ["Σεπτ", "Σεπτεμβρίου"],
        "oct"       : ["Οκτ",  "Οκτωβρίου"],
        "october"   : ["Οκτ",  "Οκτωβρίου"],
        "november"  : ["Νοεμ", "Νοεμβρίου"],
        "nov"       : ["Νοεμ", "Νοεμβρίου"],
        "december"  : ["Δεκ",  "Δεκεμβρίου"],
        "dec"       : ["Δεκ",  "Δεκεμβρίου"]
    }

    /**
     * ReplaceAll function for strings.
     *
     * @type {string}
     */
    String.prototype.replaceAll = function(
        strTarget, // The substring you want to replace
        strSubString // The string you want to replace in.
        ){
        var strText = this;
        var intIndexOfMatch = strText.indexOf( strTarget );

        // Keep looping while an instance of the target string
        // still exists in the string.
        while (intIndexOfMatch != -1){
            // Relace out the current instance.
            strText = strText.replace( strTarget, strSubString )

            // Get the index of any next matching substring.
            intIndexOfMatch = strText.indexOf( strTarget );
        }

        // Return the updated string with ALL the target strings
        // replaced out with the new substring.
        return( strText );
    }

    /**
     * Casts the first character in a string to uppercase with greek σ support.
     *
     * @type {string}
     */
    String.prototype.uCaseFirst = function(){
        return this.substr(0, 1).toUpperCase() + this.substr(1).toLowerCase().replace(/σ( +)|σ$/g, 'ς$1');
    };

    /**
     * Uppercase the first character of every word in a string with greek σ support.
     *
     * @type {string}
     */
    String.prototype.uCaseWords = function(){
        return (this+'').toLowerCase().replace(/σ( +)|σ$/g, 'ς$1').replace(/^(.)|\s(.)/g, function ($1) {
            return $1.toUpperCase( );
        });
    };

    jsbApp.runApp = function(){
        if(typeof jsbApp.admin != 'undefined' && typeof jsbApp.admin != null)
            jsbApp.admin.userMode();

        jsbApp.events();

        $(window).bind('hashchange', function(){
            jsbApp.siteNav(jsbApp.hashHandling.getHash());
        });

        $(window).trigger( 'hashchange' );
    }

    /*
     * Init and run our app
     *
     */
    jsbApp.initApp = function(){
        for(var i in jsbApp.appProps.langs) {
            jsbApp.globalVars.availableLangs.push(i);
            jsbApp.globalVars.contentData[i] = {}
            jsbApp.globalVars.contentData[i].pages = {}
        }

        jsbApp.sanitizePath();
        
        jsbApp.getTemps(function(){
            jsbApp.runApp();
        });
    };

    /*
     * App exit
     * Clear everything
     */
    $(window).bind("unload", function() {
        jsbApp = {};
    });

    $(window).ready(function () {
        //hide the site loader if is still there - ie bug
        var $siteLoading = $(document.getElementById('siteLoading'));
        if($siteLoading.length > 0) {
            setTimeout(function(){
                $siteLoading.fadeOut('normal', function (){
                    $siteLoading.remove();
                    document.getElementsByTagName('body')[0].style.overflow = '';
                });
            }, 100);
        }
    });

    $(document).ready(function(){
        $('.comment').remove();
        jsbApp.webStart.init();
        jsbApp.twitter.feed();
        jsbApp.initApp();
    });
})(jsbApp);