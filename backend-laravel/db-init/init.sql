-- USERS TABLE 
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    user_birthday DATE NOT NULL,
    token_last_used_at TIMESTAMP,
    token_expires_at TIMESTAMP,
    token VARCHAR(255)
);

ALTER TABLE users
ADD COLUMN email_verification_code VARCHAR(255) NULL,
ADD COLUMN verification_code_expires_at TIMESTAMP NULL;

ALTER TABLE users
ADD COLUMN email_verified_at TIMESTAMP NULL;