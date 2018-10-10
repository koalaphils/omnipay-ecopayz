function phpjs_dateFormat (format) {
    if (typeof format === 'undefined' || format === null) {
        return null;
    }

    var phpFormats = {
        // day
        'd': 'DD',
        'D': 'ddd',
        'j': 'D',
        'l': 'dddd',
        'N': 'E',
        'S': 'Do',
        'w': 'd',
        'z': 'DDD',
        // week
        'W': 'W',
        // month
        'F': 'MMMM',
        'm': 'MM',
        'M': 'MMM',
        'n': 'M',
        // year
        // 'L': '',
        'o': 'Y',
        'Y': 'YYYY',
        'y': 'YY',
        // time
        'a': 'a',
        'A': 'A',
        'B': 'SSS',
        'g': 'h',
        'G': 'k',
        'h': 'hh',
        'H': 'kk',
        'i': 'mm',
        's': 'ss',
        // timezone
        'e': 'zz',
        // 'I': '',
        'O': 'ZZ',
        'P': 'Z',
        'T': 'z',
        //'Z': '',
        'c': 'YYYY-MM-DDTHH:mm:ssZ',
        'r': 'ddd, D MMM YYYY HH:mm:ss ZZ',
        'U': 'X'
    };

    var len = format.length;
    var jsFormat = "";
    for (var i = 0; i < len; i++) {
        var char = format.charAt(i);
        if (char == '\\') {
            i++;
            char = format.charAt(i);
            jsFormat += "[" + char + "]";
        } else if (typeof phpFormats[char] === 'undefined') {
            jsFormat += char;
        } else {
            jsFormat += phpFormats[char];
        }
    }

    return jsFormat;
}