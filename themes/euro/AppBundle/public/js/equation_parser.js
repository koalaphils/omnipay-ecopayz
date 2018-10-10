var EqParse = function(options) {};

EqParse.prototype = {
    'parse': function(str, vars, compute) {
        var index = 0;
        var array_parse = [];
        var type = null;
        
        for(var i = 0; i < str.length; i++) {
            var char = str[i];
            if (char == '(') {
                
                if (type == 'num' || type == 'arr') {
                    index++;
                    array_parse[index] = '*';
                    index++;
                }
                
                type = 'arr';
                var parse = "";
                var open = 0;
                
                for(var ii = i+1; ii < str.length;ii++) {
                    if (str[ii] == "(") {
                        open++;
                    }
                    if(str[ii] == ")") {
                        open--;
                    }
                    
                    if(open == -1) {
                        break;
                    }
                    else {
                        parse += '' + str[ii];
                    }
                }
                
                i = ii;
                
                if (typeof array_parse[index] != 'undefined') {
                    index++;
                }
                if (compute){
                    array_parse[index] = this.parse(parse, vars, compute);
                }
                    
                else { 
                    array_parse[index] = '(' + this.parse(parse, vars, compute) + ')';
                }
                
            } else if (this.isOperators(char) && type != 'opr' && null !== type) {
                if (typeof array_parse[index] != 'undefined') {
                    index++;
                }
                type = 'opr';
                array_parse[index] = char;
            } else {
                if (typeof array_parse[index] != 'undefined' && type != 'num') {
                   if (type == 'arr') {
                       index++;
                       array_parse[index] = '*';
                   }
                   index++;
                   array_parse[index] = '';
                } else if (typeof array_parse[index] == 'undefined') {
                    array_parse[index] = ''
                }
                
                type = 'num';
                array_parse[index] += '' + char;
            }
        }
        this.subtitute(array_parse, vars);
        if (true === compute) {
            return (new Decimal(this.compute(array_parse, vars))).toString();
        }
        return array_parse.join('');
    },
    'subtitute': function(array_parse, vars) {
        vars = vars || {};
        for ( var i in array_parse ) {
            if( !this.isOperators(array_parse[i]) ) {
                if ( typeof array_parse[i] == 'string' && typeof vars[array_parse[i]] != 'undefined' ) {
                    array_parse[i] = vars[array_parse[i]];
                }
            }
        }
    },
    'compute': function(array_parse, vars) {
        var new_array = array_parse;
        for( var x in this.getOperators() ) {
            var opr = this.getOperators()[x];
            for( var index = 0; index < new_array.length; index++ ) {
                var val = new_array[index];
                if(this.isOperators(val) && val === opr) {
                    var num1 = new_array[index-1];
                    var num2 = new_array[index+1];
                    var num = new Decimal(num1);
                    
                    switch(val) {
                        case '+':
                            num = num.plus(num2);
                            break;
                        case '-':
                            num = num.minus(num2);
                            break;
                        case '*':
                            num = num.times(num2);
                            break;
                        case '/':
                            num = num.dividedBy(num2);
                            break;
                    }
                    
                    new_array.splice(index-1, 3, num.toString());
                    index-=1;
                }
            }
        }
        return new_array[0];
    },
    'getOperators': function() {
        return ['*','/','+','-'];
    },
    'isOperators': function(opr) {
        if(typeof opr !== "string") return false;
        var has = false;
        for(var _opr in this.getOperators()) {
            if(this.getOperators()[_opr] == opr) {
                has = true;
                break;
            }
        }
        return has;
    }
}
