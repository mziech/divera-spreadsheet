function activateFreezePane(coordinate) {
    console.log("Activating freeze pane", coordinate);
    var endColumn = coordinate.replace(/^([A-Z]+)[0-9]+$/, "$1").charCodeAt(0) - 'A'.charCodeAt(0) + 1;
    var endRow = parseInt(coordinate.replace(/^[A-Z]+([0-9]+)$/, "$1"));
    var table = document.getElementsByTagName("table")[0];
    var trs = table.getElementsByTagName('tr');
    var top = 0;
    for (var i = 0; i < trs.length; i++) {
        var left = 0;
        var tds = trs[i].querySelectorAll('td,th');
        for (var j = 0; j < tds.length; j++) {
            var td = tds[j];
            var z = 0;
            if (i + 1 < endRow) {
                td.style.top = top + 'px';
                z++;
            }
            if (j + 1 < endColumn) {
                td.style.left = left + 'px';
                z++;
            }
            if (z > 0) {
                td.style.position = 'sticky';
                td.style.zIndex = z;
            }
            left = left + td.offsetWidth;
        }
        top = top + trs[i].offsetHeight;
    }
}

function findFreezePanes() {
    var scripts = document.getElementsByTagName('script');
    for (var i in scripts) {
        if (!scripts[i].attributes) {
            continue;
        }

        var coordinate = scripts[i].attributes.getNamedItem('data-freezepane');
        if (coordinate) {
            activateFreezePane(coordinate.value);
        }
    }
}

findFreezePanes();
window.onresize = findFreezePanes;
