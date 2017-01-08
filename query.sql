INSERT INTO posts (wp_id,user_id,created_at,content,title,excerpt,wp_slug,updated_at,allow_comments,status,thumbnail)
SELECT ID AS wp_id,
	p.post_author AS user_id,
	p.post_date AS created_at,
	p.post_content AS content,
	p.post_title AS title,
	p.post_excerpt AS excerpt,
	p.post_name AS wp_slug,
	p.post_modified AS updated_at, 
	CASE WHEN p.comment_status = 'open' THEN 1 ELSE 0 END allow_comments, 
	CASE WHEN p.post_status = 'publish' OR p.post_status = 'pending' THEN 'public' WHEN p.post_status = 'auto-draft' THEN 'draft' ELSE p.post_status END `status`,
	pm.meta_value as 'thumbnail'
FROM wp_posts AS p
LEFT JOIN wp_postmeta as pm ON p.ID = pm.post_id AND pm.meta_key='thumbnail'
WHERE post_type='post'
ORDER BY created_at