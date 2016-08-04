$.validator.addMethod("regex", function(value, element, regexp) {
    var flags   = regexp.replace(/.*\/([gimy]*)$/, '$1'),
        pattern = regexp.replace(new RegExp('^/(.*?)/' + flags + '$'), '$1'),
        regex   = new RegExp(pattern, flags);

    return this.optional(element) || regex.test(value);
}, 'Format is invalid');