-- USERS TABLE 
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(15) NOT NULL,
    user_password VARCHAR(15) NOT NULL,
    user_birthday DATE NOT NULL,
    token_last_used_at TIMESTAMP,
    token_expires_at TIMESTAMP,
    token VARCHAR(255)
);