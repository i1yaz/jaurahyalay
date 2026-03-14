<script>
    setTimeout(function() {
        const getCookie = (name) => {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        };

        const fbp = getCookie('_fbp');
        const fbc = getCookie('_fbc');
        
        const eventId = "evt_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9);

        fetch('https://tracking.pigeonclub.top/api/meta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_name: 'ViewContent',
                event_id: eventId,
                fbp: fbp,
                fbc: fbc,
                source_url: window.location.href
            })
        }).catch(() => {}); 

        if (typeof fbq === 'function') {
            fbq('track', 'ViewContent', {}, {
                eventID: eventId
            });
        }
    }, 2000);
</script>