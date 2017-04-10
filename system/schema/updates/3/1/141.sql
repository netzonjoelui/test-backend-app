-- Fix issue with the title field being text(256) for older databases
ALTER table xml_feed_posts alter column title type text;