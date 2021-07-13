function initiateMap(mapNumer, focusX = 0, focusY = 0) {
    var container, img, blip;
    var dragging = false;
    var pos1, pos2, pos3, pos4;
    var lastPinch = null;
    var originalWidth, originalHeight;
    var scalar = 1;
    var touchDragging = false;
    var touchZooming = false;
    var blipPos = {x:0, y:0};
    
    container = document.getElementById('map' + mapNumer);
    blip = document.getElementById('blip' + mapNumer);
    
    if(container !== null) {
        var c = container.children;
        var i;
        blipPos = {x:focusX, y:focusY};
        for (i = 0; i < c.length; i++) {
            if(c[i].tagName == "IMG") {
                img = c[i];
                originalWidth = img.width;
                originalHeight = img.height;
                break;
            }
        }
        
        if(img !== undefined) {
            img.addEventListener("mousedown", startDrag);
            img.addEventListener("touchstart", startTouchDrag, false);
            container.addEventListener("wheel", onWheel);
            
            if(focusX > 0 || focusY > 0) {
                img.addEventListener("load", function() {
                    panImageTo((-focusX * img.naturalWidth * scalar) + (container.offsetWidth / 2), (-focusY * img.naturalHeight * scalar) + (container.offsetHeight / 2));
                });
            }
        }
        
        
        if(blip !== null) {
            blip.style.location = "absolute";
            blip.innerHTML = "<img src=\"http://ayaseye.com/icons/map_blip.png\" style=\"z-index:2;\">";
        }
        
        
    }
    
    function panImage(shiftX = 0, shiftY = 0) {
        var x, y;
        
        x = img.offsetLeft - shiftX;
        if(x > 0) x = 0;
        if(-x > img.offsetWidth - container.offsetWidth) x = -(img.offsetWidth - container.offsetWidth);
        y = img.offsetTop - shiftY;
        if(y > 0) y = 0;
        if(-y > img.offsetHeight - container.offsetHeight) y = -(img.offsetHeight - container.offsetHeight);
        img.style.left = x + "px";
        img.style.top = y + "px";
        blip.style.left = (x + (blipPos.x * img.naturalWidth * scalar) - (blip.offsetWidth / 2)) + "px";
        blip.style.top = (y + (blipPos.y * img.naturalHeight * scalar) - (blip.offsetHeight / 2)) +"px";
    }
    
    function panImageTo(coordX = 0, coordY = 0) {
        panImage(img.offsetLeft - coordX, img.offsetTop - coordY);
    }
    
    function doZoom(amount = 0, focalX = 0, focalY = 0) {
		var oldWidth, oldHeight, x, y, rect;
        oldWidth = img.width;
        oldHeight = img.height;
        
        if(scalar === undefined) scalar = 1;
        
        scalar = scalar + scalar * amount;
        if(scalar > 1000) scalar = 1000;
        if(img.naturalWidth * scalar < container.clientWidth) {
            scalar = container.clientWidth / img.naturalWidth;
        }
        if(img.naturalHeight * scalar < container.clientHeight) {
            scalar = container.clientHeight / img.naturalHeight;
        }
        
        img.width = img.naturalWidth * scalar;
        
        rect = container.getBoundingClientRect();
        x = (((img.offsetLeft - (focalX - rect.left)) / oldWidth) * img.width) + (focalX- rect.left);
        y = (((img.offsetTop - (focalY - rect.top)) / oldHeight) * img.height) + (focalY - rect.top);
        
        if(x > 0) x = 0;
        if(-x > img.offsetWidth - container.offsetWidth) x = -(img.offsetWidth - container.offsetWidth);
        if(y > 0) y = 0;
        if(-y > img.offsetHeight - container.offsetHeight) y = -(img.offsetHeight - container.offsetHeight);
        img.style.left = x + "px";
        img.style.top = y + "px";
        blip.style.left = (x + (blipPos.x * img.naturalWidth * scalar) - (blip.offsetWidth / 2)) + "px";
        blip.style.top = (y + (blipPos.y * img.naturalHeight * scalar) - (blip.offsetHeight / 2)) +"px";
    }
    
    function onWheel(e) {
        e = e || window.event;
        e.preventDefault();
        
        doZoom(e.deltaY * -0.001, e.clientX, e.clientY);
    }
    
    function touchDragImage(e) {
        e = e || window.event;
        e.preventDefault();
        
        if(e.touches.length === 1) {
            pos1 = pos3 - e.touches[0].clientX;
            pos2 = pos4 - e.touches[0].clientY;
            pos3 = e.touches[0].clientX;
            pos4 = e.touches[0].clientY;
            
            panImage(pos1, pos2);
        } else if(e.touches.length === 2) {
            if(lastPinch === null) {
                lastPinch = [{x:e.touches[0].clientX, y:e.touches[0].clientY}, {x:e.touches[1].clientX, y:e.touches[1].clientY}];
            } else {
                var newPinch = [{x:e.touches[0].clientX, y:e.touches[0].clientY}, {x:e.touches[1].clientX, y:e.touches[1].clientY}];
                
                var dist1 = Math.sqrt(Math.pow(lastPinch[0].x - lastPinch[1].x, 2) + Math.pow(lastPinch[0].y - lastPinch[1].y, 2));
                var dist2 = Math.sqrt(Math.pow(newPinch[0].x - newPinch[1].x, 2) + Math.pow(newPinch[0].y - newPinch[1].y, 2));
                var amnt = (dist2 - dist1) * 0.01;
                var center = {x:(newPinch[0].x + newPinch[1].x) / 2, y:(newPinch[0].y + newPinch[1].y) / 2};
                
                doZoom(amnt, center.x, center.y);
                
                lastPinch = newPinch;
            }
        }
        
    }
    
    function dragImage(e) {
        e = e || window.event;
        e.preventDefault();
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        
        panImage(pos1, pos2);
    }
    
    function startTouchDrag(e) {
        e = e || window.event;
        e.preventDefault();
        
        if(e.touches.length === 1) {
            if(!touchDragging) {
                pos3 = e.touches[0].clientX;
                pos4 = e.touches[0].clientY;
                document.addEventListener("touchmove", touchDragImage, false);
                document.addEventListener("touchend", endTouchDrag, false);
                document.addEventListener("touchcancel", endTouchDrag, false);
                touchDragging = true;
            }
        } else if(e.touches.length === 2) {
            
        }
        
    }
    
    function startDrag(e) {
        e = e || window.event;
        e.preventDefault();
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.addEventListener("mousemove", dragImage);
        document.addEventListener("mouseup", endDrag);
        
    }
    
    function endTouchDrag(e) {
        if(e.touches.length === 0) {
            document.removeEventListener("touchmove", dragImage);
            document.removeEventListener("touchend", endTouchDrag);
            document.removeEventListener("touchcancel", endTouchDrag);
            touchDragging = false;
            touchZooming = false;
            lastPinch = null;
        }
    }
    
    function endDrag(e) {
        document.removeEventListener("mousemove", dragImage);
        document.removeEventListener("mouseup", endDrag);
    }
}
