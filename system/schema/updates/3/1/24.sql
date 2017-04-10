-- Prepare userfiles to be moved to AntFs
UPDATE user_files SET f_ans_cleaned='f' WHERE f_deleted IS FALSE AND ans_key IS NULL;
