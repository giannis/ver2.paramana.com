/*!
 * admin.paramana.com
 * Version: 2.0
 * Started: 13-11-2010
 * Updated: 13-11-2010
 * Url    : http://admin.paramana.com
 * Author : giannis (giannis AT paramana DOT com)
 *
 * Copyright (c) 2010 paramana.com
 *
 */

/*
 * Self calling function for our app
 *
 */
(function(jsbApp){
    /*
     * This is used for the admin area
     */
    jsbApp.admin = {
        canEdit: false,
        userMode: function(){
            if(jsbApp.sessionid)
                return this.canEdit = true;//jsbApp.sessionid;
            return this.canEdit = false;
        },
        /*
         *
         *
         */
        initiator: function(callback){
            if(this.jsReady) {
                if(callback)
                    callback();
                return;
            }

            for(var i in jsbApp.globalVars.templates) {
                this.parseTemps(i);
            }

            this.jsReady = true;

            if(callback)
                callback();
        },
        dataLookup: [],
        tags: {
            "wrap": {
				doclose: true,
				open: '<span class="editable" edit_type="wrap_">',
                close: '</span>'
			},
//            'forin': {
//                open: '<span class="editable" edit_type="forin_">',
//                close: '</span>'
//            },
			"if": {
				open: '<span class="editable" edit_type="if_">',
                close: '</span>'
			},
            'elseif': {
                open: '<span class="editable" edit_type="elseif_">',
                close: '</span>'
            },
			"else": {
				open: '<span class="editable" edit_type="else_">',
                close: '</span>'
			},
			"html": {
                doclose: true,
				open: '<span class="editable" edit_type="html_">',
                close: '</span>'
			},
            "@": {
				doclose: true,
				open: '<span class="editable" edit_type="text2_">',
                close: '</span>'
			},
			"!": {
				doclose: true,
				open: '',
                close: ''
			},
			"each": {
				open: '<span class="editable" edit_type="each_">',
                close: '</span>'
			},
            "output": {
                doclose: true,
				open: '<span class="editable" edit_type="output_">',
                close: '</span>'
			},
            "root": {
				doclose: true,
				open: '<span class="editable" edit_type="root_">',
                close: '</span>'
			},
			"=": {
                doclose: true,
				open: '<span class="editable" edit_type="text_">',
                close: '</span>'
			},
            "img": {
                editType: "img_",
                matchAttrs: ['src', 'alt', 'title', 'width', 'height', 'class', 'id'],
				open: '<span class="editable" edit_type="img_">',
				close: '</span>'
			},
            "a": {
                editType: "link_",
                matchAttrs: ['href', 'title', 'rel', 'class', 'id'],
				open: '<span class="editable" edit_type="link_">',
				close: '</span>'
			},
            "id": {
                editType: "id_",
                matchAttrs: ['id'],
				open: '<span class="editable" edit_type="id_">',
				close: '</span>'
			},
            "class": {
                editType: "class_",
                matchAttrs: ['class'],
				open: '<span class="editable" edit_type="class_">',
				close: '</span>'
			}
        },
        /*
         * Converts the template for use when user is logged in
         *
         */
        parseTemps: function(tempId){
//            (?:[\w]*) *= *"%(?:(?:(?:(?:(?:\\\W)*\\\W)*[^"]*)\\\W)*[^"]*")
//            <([^\s>]*)(\s[^<]*)>
//            (\S+)=["']?((?:.(?!["']?\s+(?:\S+)=|[>"']))+.)["']?
//
//
//            <\/?\w+((\s+(\w|\w[\w\-]*\w)(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)\/?>
//
//            <\/?\w+((\s+(\w|\w[\w\-]*\w)\s*=\s*((?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)\/?>

//<\/?\w+(\s+([^\s=]+)\s*=\s*('%*[^<\']*'|"[^<"]*[%][^<"]*")+\s*|\s*)\/?>
            var _self = this,
               gVars  = jsbApp.globalVars,
               temp   = gVars.templates[tempId],
               markup = temp.markup;
            //@todo cases to catch:
            //this.title.replace("+","<br/>+")
            //root([id])
            //gallery[0]
            //this.bg
            //jsbApp.test(sdfsdf);
            //
            //match the tags in the template that are template commands ex: <div id="%= pageid%Page">
            //and wrap the tag element with a class and the edit type plus the reference for the data
            gVars.templates[tempId].markup = markup.replace(/(?:%(\/?)(\w+|.)(?:\(((?:[^\%]|\%(?!\%))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\%]|\%(?!\%))*?)\))?\s*%)|(?:<(\w+)(?:\s+[^\s=]+\s*=\s*(?:'%*[^<\']*'|"[^<"]*[^<"]*")+\s*)*(\s+([^\s=]+)\s*=\s*('%*[^<\']*'|"[^<"]*[%][^<"]*")+\s*)+(?:\s+[^\s=]+\s*=\s*(?:'%*[^<\']*'|"[^<"]*[^<"]*")+\s*)*[^<]*\s*\/?>)/g,
               function(all, slash, type, fnargs, target, parens, args, tagName, allAttr, attrName, value) {
                   var tag;
                   if(!allAttr) {
                       tag = _self.tags[type];
                       var openTag  = '';
                       if(!slash) {
                           openTag = tag[ "open" ];
                           if(!parens && !args) {
                               _self.dataLookup.push(fnargs.replace(/\[|\]/g, '') || target);
                               openTag = openTag.split('_"').join('_' + (_self.dataLookup.length - 1) + '"');
                           }
                       }
                       return openTag + all + (slash || tag.doclose ? tag[ "close" ] : '');
                   }
                   else {
                       tag = _self.tags[tagName] || _self.tags[attrName];
                       //those are created dynamically so we do not need to do anything
                       if(all.indexOf('$index') > -1 || all.indexOf('$value') > -1)
                           return all;

                       _self.dataLookup.push(jQuery.trim(value.replace(/".*%(?:wrap|each|forin|if|elseif|else|html|output|=|@|!)*(.+)%.*"/, '$1').split('"').join("&#34;").split("'").join("&#39;")));
                       return all.replace(/(class\s*=\s*('%*[^<\']*'|"[^<"]*[^<"]*")+)/, function(allClass, classValue){
                                     return allClass.split(/^class="/).join('edit_type="' + tag.editType + '" class="editable ');
                                 });

                       return all.split(tagName).join(tagName + ' class="editable" edit_type="' + tag.editType + '"');
                   }
               }
            );
        }
//        auto tha paei sta each
//        var k = {"carouselitems": [
//                    {
//                        "title": "Φτιάχνουμε τα sites που<br />&nbsp;&nbsp;&nbsp;&nbsp; πάντα θέλατε να είχατε!",
//                        "text": "Με τις τελευταίες τεχνολογιες του Internet κατασκευάζουμε αξιοπιστα συστηματα που καλυπτουν τους στοχους σας και τις αναγκες του κοινου σας.<br /><br /><a href=\"work.html\">Δείτε δείγματα της δουλείας μας &raquo;</a><br /><a href=\"services.html\">Τι μπορουμε να κάνουμε για σας &raquo;</a><br /><a href=\"contact.html\">Ζητήστε μας προσφορά &raquo;</a>",
//                        "bg": "images/carousel/s1.png"
//                    },
//                    {
//                        "title": "Η μοναδικότητα κάθε ιστοσελίδας <br />&nbsp;&nbsp;&nbsp;&nbsp; εξασφαλίζεται από την προσοχή <br />&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; που δίνουμε στη λεπτομέρια",
//                        "text": "Η επιτυχία μιας ιστοσελιδας μετριεται στο σεβασμο για τον χρηστη της. Η προσεγμένη δουλειά μας δημιουργεί μοναδικες ιστοσελιδες που οι χρηστες σας θα  θελουν να επισκεπτονται καθημερινα.",
//                        "bg": "images/carousel/s2.png"
//                    }
//                ]};
//var u = ['sdaf',2];
//var foo = jQuery.extend(true, {}, k.carouselitems[0]);;
//if(typeof foo == 'object') {
//
//    for(var i in foo) {
//        foo[i] = '';
//    }
//}
//k.carouselitems.push(foo)
//console.log(k)
    };
})(jsbApp);