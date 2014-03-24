Modernizr.addTest('hiddenscroll', function () {
    return Modernizr.testStyles('#modernizr {width:100px;height:100px;overflow:scroll}', function (elem) {
        return elem.offsetWidth === elem.clientWidth;
    });
});
