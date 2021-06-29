var XslTemplate = function(template, isUrl) {
    isUrl = typeof isUrl != 'undefined' ? isUrl : true;
    if(isUrl)
        this.xsl = this.loadDoc(template);
    else  this.xsl = loadXml(template);
};

XslTemplate.prototype = {
    'apply': function(items) {
        if(typeof items == 'string') {
            var xml = this.loadDoc(items);
        } else {
            var xml = items;
        }
        // code for IE
        if (window.ActiveXObject)
        {
            var result = xml.transformNode(this.xsl);
        }
        // code for Chrome, Firefox, Opera, etc.
        else if (document.implementation && document.implementation.createDocument)
        {
            var xsltProcessor = new XSLTProcessor();
            xsltProcessor.importStylesheet(this.xsl);
            var result = xsltProcessor.transformToFragment(xml, document);
        }

        return result;
    },
    'getXSL': function() {
        return this.xsl;
    },
    'loadDoc': function(filename) {
        if (window.ActiveXObject)
        {
            var xhttp = new ActiveXObject("Msxml2.XMLHTTP");
        }
        else
        {
            var xhttp = new XMLHttpRequest();
        }
        xhttp.open("GET", filename, false);
        try {xhttp.responseType = "msxml-document"} catch(err) {} // Helping IE11
        xhttp.send("");
        return xhttp.responseXML;
    }
};

var json2Xml_str = function(json, name) {

    if(null !== json) {
        if(typeof json == 'object' && json['@tag'] && json['@tag'].trim() != '') {
            name = json['@tag'];
        }
    }

    try {
        var doc = $('<'+name+' />');
    } catch(e){
        var doc = $('<i index="'+ name +'" />');
    }

    if(typeof json == 'object' && null !== json) {
        if(json['@value']) {
            doc.html(json['@value']);
        } else {
            for(var x in json) {
                var item = json[x];
                if(x == '@attributes') {
                    for(var a in item) {
                        doc.attr(a, item[a]);
                    }
                } else if(typeof item == 'object'){
                    try {
                        var n = x;
                    } catch(e) {
                        var n = 'i';
                    }

                    doc.append(json2Xml_str(item, n));
                } else if(x !== '@value' && x !== '@tag'){
                    try {
                        var elem = $('<'+ x +' />');
                    } catch(e){
                        var elem = $('<i index="'+ x +'" />');
                    }

                    elem.html(item);
                    doc.append(elem);
                }
            }
        }
    } else {
        doc.html(json);
    }

    return doc[0].outerHTML;
}

var json2Xml = function(json, name) {
    var result = json2Xml_str(json, name);
    return loadXml(result);
}

var loadXml = function(txt) {
    if (window.DOMParser)
    {
        var parser = new DOMParser();
        var xmlDoc = parser.parseFromString(txt, "text/xml");
    }
    else // Internet Explorer
    {
        var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
        xmlDoc.async = false;
        xmlDoc.loadXML(txt);
    }

    return xmlDoc;
}