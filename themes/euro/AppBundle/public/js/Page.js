class Page
{
    constructor()
    {
        this.scripts = {};
        this.totalScripts = 0;
        this.loadedScripts = [];
        this.pageScripts = [];
        this.loaders = [];

    }

    addScript(path, isAsync)
    {
        if (typeof isAsync === 'undefined') {
            isAsync = true;
        }
        var page = this;
        if (typeof this.scripts[path] === 'undefined') {
            this.totalScripts+=1;
            this.scripts[path] = this.getScript(path, isAsync);
            this.scripts[path].onload = function () {
                page.loadedScripts.push(this.src);
                for (var i in page.loaders) {
                    if (!page.loaders[i].loaded) {
                        var passed = true;
                        for (var requirementIndex in page.loaders[i].requirements) {
                            if (page.loadedScripts.indexOf(page.loaders[i].requirements[requirementIndex]) === -1) {
                                passed = false;
                                break;
                            }
                        }
                        if (passed) {
                            page.loaders[i].loader(page);
                            page.loaders[i].loaded = true;
                        }
                    }
                }
            };
        }
    }

    addLoader(callable, requirements)
    {
        var _requirements = requirements || [];
        for (var i in _requirements) {
            this.addScript(_requirements[i]);
        }
        this.loaders.push({'loader': callable, 'requirements': _requirements, 'loaded': false});
    }

    load()
    {
        var page = this;
        document.addEventListener('DOMContentLoaded', function () {
            var pageScripts = document.getElementsByTagName('script');
            for (var scriptIndex in pageScripts) {
                page.loadedScripts.push(pageScripts[scriptIndex].src);
            }
            if (page.totalScripts > 0) {
                var body = document.getElementsByTagName('body')[0];
                for (var path in page.scripts) {
                    body.appendChild(page.scripts[path]);
                }
            } else {
                for (var i in page.loaders) {
                    page.loaders[i].loader(page);
                    page.loaders[i].loaded = true;
                }
            }
        });
    }

    isScriptExists(url)
    {
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].src == url) {
                return true;
            }
        }

        return typeof this.scripts[url] !== 'undefined';
    }

    getScript(url, isAsync)
    {
        if (this.isScriptExists(url)) {
            var scripts = document.getElementsByTagName('script');

            for (var i = 0; i < scripts.length; i++) {
                if (scripts[i].src == url) {
                    return scripts[i];
                }
            }
        }

        return this.generateScript(url, isAsync);
    }

    generateScript(url, isAsync)
    {
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.src = url;
        if (isAsync) {
            s.async = true;
        } else {
            s.async = false;
        }

        return s;
    }
}

var page = new Page();
page.load();