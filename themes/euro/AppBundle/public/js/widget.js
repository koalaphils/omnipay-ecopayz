class Page
{
    constructor()
    {
        this.scripts = {};
        this.totalScripts = 0;
        this.loadedScripts = [];
        this.loaders = [];
    }

    addScript(path)
    {
        var page = this;
        if (!this.isScriptExists(path)) {
            this.totalScripts+=1;
            this.scripts[path] = this.generateScript(path);
            this.scripts[path].onload = function () {
                page.loadedScripts.push(this.src);
                if (page.totalScripts == page.loadedScripts.length) {
                    for (var i in page.loaders) {
                        var passed = true;
                        for (var requirementIndex in page.loaders[i].requirements) {
                            if (page.loadedScripts.indexOf(page.loaders[i].requirements[requirementIndex]) === -1) {
                                passed = false;
                                break;
                            }
                        }
                        if (passed) {
                            page.loaders[i].loader(page);
                        }
                    }
                }
            };
        }
    }

    addLoader(callable, requirements)
    {
        var _requirements = requirements || [];
        this.loaders.push({'loader': callable, 'requirements': _requirements});
    }

    load()
    {
        var page = this;
        document.addEventListener('DOMContentLoaded', function () {
            if (page.totalScripts > 0) {
                var body = document.getElementsByTagName('body')[0];
                for (var path in page.scripts) {
                    body.appendChild(page.scripts[path]);
                }
            } else {
                for (var i in page.loaders) {
                    page.loaders[i](page);
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

    generateScript(url)
    {
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.src = url;

        return s;
    }
}

var page = new Page();
page.load();