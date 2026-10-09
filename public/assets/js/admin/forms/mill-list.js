(function(){
    console.log('mill-list here!');
    console.log('I should only fire for state agents...');
    /**
     * Holy flurking shnit!
     * page-subheading is a <p> that contains a <div> which is invalid HTML.
     * Modern browsers handle that by closing the <p> as soon as the illegal child is 
     * encountered, causing the illegal element as well as all other children following it
     * to become siblings of the <p> instead of its children.
     * However, the parent of subheading doesn't know about its new children and thus they 
     * can't be selected from the common parent either.
     */
    $(document).on('draw.dt', function (e, settings) {
        var $sh = jQuery('[bp-section="page-subheading"]').first().hide();
        // console.log('children?', $sh.children());
        var $status = $sh.find('div.dt-info');
        // console.log('status: ', $status);
        var $reset = $sh.find('a');
        // console.log('$reset?', $reset);
        $reset.remove();
        var text = $status.text().split(' (');
        $status.text(text[0]+'.');
        $sh.show();
    });

})();