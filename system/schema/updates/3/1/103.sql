UPDATE pg_attribute SET atttypmod = 256+4
WHERE attrelid = 'xml_feed_posts'::regclass
AND attname = 'title';
