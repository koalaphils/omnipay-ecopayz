function exchangeRate(amount, rate, toRate) {
    var eq = 'z(x/r)';
    var vars = {
        'z': toRate,
        'x': amount,
        'r': rate
    };
    
    var parser = new EqParse();
    return parser.parse(eq, vars, true);
}

