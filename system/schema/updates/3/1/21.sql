-- update to deal with past imports without updating sequence
SELECT setval('public.app_widgets_id_seq', 1000, true);

INSERT INTO app_widgets(class_name, title, file_name, type, description) 
    VALUES('CWidWebpage', 'Web Page', 'CWidWebpage.js', 'system', 'Embeds a webpage on your dashboard.');
