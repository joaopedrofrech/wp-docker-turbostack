vcl 4.1;

import directors;
import std;

# Backend definition
backend nginx {
    .host = "nginx";
    .port = "80";
    .connect_timeout = 60s;
    .first_byte_timeout = 60s;
    .between_bytes_timeout = 60s;
    .max_connections = 300;
    .probe = {
        .url = "/health";
        .timeout = 5s;
        .interval = 10s;
        .window = 5;
        .threshold = 3;
    }
}

# Access Control List (ACL) for purging
acl purge {
    "127.0.0.1";
    "::1";
    "nginx";
    "wordpress";
    "10.0.0.0"/8;
    "172.16.0.0"/12;
    "192.168.0.0"/16;
}

# Removed unused ACL to fix VCL compilation

sub vcl_recv {
    # Set backend
    set req.backend_hint = nginx;

    # Remove empty query string parameters (WP Rocket compatibility)
    if (req.url ~ "\?$") {
        set req.url = regsub(req.url, "\?$", "");
    }

    # Remove port number from host header
    set req.http.Host = regsub(req.http.Host, ":[0-9]+", "");

    # Sorts query string parameters alphabetically for cache normalization
    set req.url = std.querysort(req.url);

    # Remove the proxy header to mitigate httpoxy vulnerability
    unset req.http.proxy;

    # WP Rocket compatible PURGE logic
    if (req.method == "PURGE") {
        if (!client.ip ~ purge) {
            return (synth(405, "PURGE not allowed for this IP address"));
        }
        if (req.http.X-Purge-Method == "regex") {
            ban("obj.http.x-url ~ " + req.url + " && obj.http.x-host == " + req.http.host);
            return (synth(200, "Purged"));
        }
        ban("obj.http.x-url == " + req.url + " && obj.http.x-host == " + req.http.host);
        return (synth(200, "Purged"));
    }

    # Only handle relevant HTTP request methods
    if (req.method != "GET" &&
        req.method != "HEAD" &&
        req.method != "PUT" &&
        req.method != "POST" &&
        req.method != "PATCH" &&
        req.method != "TRACE" &&
        req.method != "OPTIONS" &&
        req.method != "DELETE") {
        return (pipe);
    }

    # Mark static files and remove cookies (WP Rocket compatible)
    if (req.url ~ "^[^?]*\.(7z|avi|bmp|bz2|css|csv|doc|docx|eot|flac|flv|gif|gz|ico|jpeg|jpg|js|less|mka|mkv|mov|mp3|mp4|mpeg|mpg|odt|ogg|ogm|opus|otf|pdf|png|ppt|pptx|rar|rtf|svg|svgz|swf|tar|tbz|tgz|ttf|txt|txz|wav|webm|webp|woff|woff2|xls|xlsx|xml|xz|zip)(\?.*)?$") {
        set req.http.X-Static-File = "true";
        unset req.http.Cookie;
        return (hash);
    }

    # Only cache GET and HEAD requests
    if (req.method != "GET" && req.method != "HEAD") {
        set req.http.X-Cacheable = "NO:REQUEST-METHOD";
        return (pass);
    }

    # WordPress specific: don't cache logged in users
    if (req.http.Cookie ~ "wordpress_logged_in_|wp-settings-") {
        return (pass);
    }

    # Don't cache admin area
    if (req.url ~ "wp-admin|wp-login|wp-cron|xmlrpc\.php") {
        return (pass);
    }

    # Don't cache AJAX requests
    if (req.url ~ "wp-admin/admin-ajax\.php") {
        return (pass);
    }

    # Don't cache WooCommerce pages (CloudPanel optimized)
    if (req.url ~ "^/my-account/") {
        return (pass);
    }
    
    if (req.url ~ "/cart/") {
        return (pass);
    }
    
    if (req.url ~ "/checkout/") {
        return (pass);
    }
    
    if (req.url ~ "wp-login.php") {
        return (pass);
    }

    # Don't cache preview pages
    if (req.url ~ "preview=true|preview_id=") {
        return (pass);
    }

    # Don't cache search results
    if (req.url ~ "\?s=") {
        return (pass);
    }

    # Remove excluded params (CloudPanel style)
    if (req.url ~ "(\?|&)(__SID|noCache)=") {
        set req.url = regsuball(req.url, "&(__SID|noCache)=([A-z0-9_\-\.%25]+)", "");
        set req.url = regsuball(req.url, "\?(__SID|noCache)=([A-z0-9_\-\.%25]+)", "?");
        set req.url = regsub(req.url, "\?&", "?");
        set req.url = regsub(req.url, "\?$", "");
    }

    # Remove tracking parameters
    if (req.url ~ "(\?|&)(utm_source|utm_medium|utm_campaign|utm_content|gclid|cx|ie|cof|siteurl)=") {
        set req.url = regsuball(req.url, "&(utm_source|utm_medium|utm_campaign|utm_content|gclid|cx|ie|cof|siteurl)=([A-z0-9_\-\.%25]+)", "");
        set req.url = regsuball(req.url, "\?(utm_source|utm_medium|utm_campaign|utm_content|gclid|cx|ie|cof|siteurl)=([A-z0-9_\-\.%25]+)", "?");
        set req.url = regsub(req.url, "\?&", "?");
        set req.url = regsub(req.url, "\?$", "");
    }

    # Remove cookies for static content
    if (req.url ~ "^[^?]*\.(css|js|png|gif|jp(e?)g|swf|ico|pdf|txt|gz|zip|lzma|bz2|tgz|tbz|html|htm)(\?.*)?$") {
        unset req.http.Cookie;
        return (hash);
    }

    # Remove WordPress cookies for anonymous users
    if (req.http.Cookie) {
        set req.http.Cookie = ";" + req.http.Cookie;
        set req.http.Cookie = regsuball(req.http.Cookie, "; +", ";");
        set req.http.Cookie = regsuball(req.http.Cookie, ";(wp-settings-\d+|wordpress_logged_in_[^;]*)", "");
        set req.http.Cookie = regsuball(req.http.Cookie, "^;", "");

        if (req.http.Cookie == "") {
            unset req.http.Cookie;
        }
    }

    # Handle compression
    if (req.http.Accept-Encoding) {
        if (req.url ~ "\.(jpg|png|gif|gz|tgz|bz2|tbz|mp3|ogg)$") {
            unset req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            unset req.http.Accept-Encoding;
        }
    }

    return (hash);
}

sub vcl_backend_response {
    # Inject URL & Host header for WP Rocket purging
    set beresp.http.x-url = bereq.url;
    set beresp.http.x-host = bereq.http.host;

    # If we dont get a Cache-Control header, default to 1h for all objects
    if (!beresp.http.Cache-Control) {
        set beresp.ttl = 1h;
        set beresp.http.X-Cacheable = "YES:Forced";
    }

    # Static files - cache for 1 day and remove cookies
    if (bereq.http.X-Static-File == "true") {
        unset beresp.http.Set-Cookie;
        set beresp.http.X-Cacheable = "YES:Forced";
        set beresp.ttl = 1d;
    }

    # Remove Set-Cookie for Wordfence compatibility
    if (beresp.http.Set-Cookie ~ "wfvt_|wordfence_verifiedHuman") {
        unset beresp.http.Set-Cookie;
    }

    # Set cacheable headers for debugging
    if (beresp.http.Set-Cookie) {
        set beresp.http.X-Cacheable = "NO:Got Cookies";
    } elseif(beresp.http.Cache-Control ~ "private") {
        set beresp.http.X-Cacheable = "NO:Cache-Control=private";
    }
}



sub vcl_hit {
    # Handle PURGE requests
    if (req.method == "PURGE") {
        return (synth(200, "Purged"));
    }

    return (deliver);
}

sub vcl_miss {
    # Handle PURGE requests
    if (req.method == "PURGE") {
        return (synth(404, "Not in cache"));
    }

    return (fetch);
}

sub vcl_hash {
    hash_data(req.url);
    
    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }
    
    # Create cache variations for HTTPS (WP Rocket compatibility)
    if (req.http.X-Forwarded-Proto) {
        hash_data(req.http.X-Forwarded-Proto);
    }
    
    # Include mobile detection in hash (opcional)
    if (req.http.User-Agent ~ "(?i)(mobile|android|iphone|ipad)") {
        hash_data("mobile");
    }
}

sub vcl_deliver {
    # Cache status headers
    if (obj.hits > 0) {
        set resp.http.X-Varnish-Cache = "HIT";
        set resp.http.X-Cache-Hits = obj.hits;
    } else {
        set resp.http.X-Varnish-Cache = "MISS";
    }

    # Debug headers for WP Rocket compatibility
    if (req.http.X-Cacheable) {
        set resp.http.X-Cacheable = req.http.X-Cacheable;
    } elseif (obj.uncacheable) {
        if (!resp.http.X-Cacheable) {
            set resp.http.X-Cacheable = "NO:UNCACHEABLE";
        }
    } elseif (!resp.http.X-Cacheable) {
        set resp.http.X-Cacheable = "YES";
    }

    # Cleanup internal headers
    unset resp.http.x-url;
    unset resp.http.x-host;
    unset resp.http.Via;
    unset resp.http.X-Varnish;
}
