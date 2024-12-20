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

ALTER TABLE users
ADD COLUMN login_attempts INT DEFAULT 0,
ADD COLUMN last_login_attempt_at TIMESTAMP NULL,
ADD COLUMN reset_attempts_token VARCHAR(255) NULL,
ADD COLUMN reset_attempts_token_expires_at TIMESTAMP NULL;

ALTER TABLE users
ADD COLUMN verification_attempts INT DEFAULT 0,
ADD COLUMN last_verification_attempt_at TIMESTAMP NULL,
ADD COLUMN reset_verification_attempts_token VARCHAR(255) NULL,
ADD COLUMN reset_verification_attempts_token_expires_at TIMESTAMP NULL;


/* ================================================ */
/* ================================================ */
/* ================================================ */
/* ================================================ */

CREATE TABLE crypto (
    id SERIAL PRIMARY KEY,
    label VARCHAR(255) NOT NULL    
);

CREATE TABLE crypto_cours (
    id SERIAL PRIMARY KEY,
    id_crypto INT NOT NULL REFERENCES crypto(id),
    cours DECIMAL(10, 2) NOT NULL,
    date_cours DATETIME NOT NULL,
);

CREATE TABLE transaction_crypto (
    id SERIAL PRIMARY,
    id_crypto INT NOT NULL REFERENCES crypto(id),
    id_seller INT NOT NULL REFERENCES user(id),
    id_buyer INT NOT NULL REFERENCES user(id),
    qte_vendue DECIMAL(10, 2) NOT NULL,
    date_transaction DATETIME NOT NULL
);

CREATE TABLE transaction_fond (
    id SERIAL PRIMARY KEY,
    id_user INT NOT NULL REFERENCES users(id),
    id_transaction_crypto INT REFERENCES transaction_crypto(id), -- null if depot or retrait 
    depot DECIMAL(10, 2) DEFAULT 0,
    retrait DECIMAL(10, 2) DEFAULT 0,
    date_transaction DATETIME NOT NULL   
);


/* ================================================ */
/* ================================================ */
/* ================================================ */
/* ================================================ */

--
-- achats
-- 
CREATE OR REPLACE VIEW v_user_achats AS
SELECT
    u.id AS user_id,
    u.name AS user_name,
    tc.id_crypto,
    c.label AS crypto_label,
    tc.qte_vendue AS quantity_bought,
    tc.date_transaction AS transaction_date,
    cc.cours AS price_per_unit,
    (tc.qte_vendue * cc.cours) AS total_cost
FROM
    transaction_crypto tc
JOIN
    users u ON u.id = tc.id_buyer
JOIN
    crypto c ON c.id = tc.id_crypto
LEFT JOIN
    crypto_cours cc ON cc.id_crypto = c.id 
    AND cc.date_cours = tc.date_transaction;

--
-- ventes
--
CREATE OR REPLACE VIEW v_user_ventes AS
SELECT
    u.id AS user_id,
    u.name AS user_name,
    tc.id_crypto,
    c.label AS crypto_label,
    tc.qte_vendue AS quantity_sold,
    tc.date_transaction AS transaction_date,
    cc.cours AS price_per_unit,
    (tc.qte_vendue * cc.cours) AS total_revenue
FROM
    transaction_crypto tc
JOIN
    users u ON u.id = tc.id_seller
JOIN
    crypto c ON c.id = tc.id_crypto
LEFT JOIN
    crypto_cours cc ON cc.id_crypto = c.id 
    AND cc.date_cours = tc.date_transaction;

--
-- update_transaction_fond
--
CREATE OR REPLACE FUNCTION f_update_transaction_fond()
RETURNS TRIGGER AS $$
BEGIN
    -- Insert a record for the seller to reflect their revenue (retrait)
    INSERT INTO transaction_fond (
        id_user,
        id_transaction_crypto,
        depot,
        retrait,
        date_transaction
    )
    VALUES (
        NEW.id_seller,
        NEW.id,
        0, -- no deposit for seller
        NEW.qte_vendue * (
            SELECT cours 
            FROM crypto_cours 
            WHERE id_crypto = NEW.id_crypto 
            AND date_cours = NEW.date_transaction
            LIMIT 1
        ), -- retrait is revenue for the seller
        NEW.date_transaction
    );

    -- Insert a record for the buyer to reflect their expense (depot)
    INSERT INTO transaction_fond (
        id_user,
        id_transaction_crypto,
        depot,
        retrait,
        date_transaction
    )
    VALUES (
        NEW.id_buyer,
        NEW.id,
        NEW.qte_vendue * (
            SELECT cours 
            FROM crypto_cours 
            WHERE id_crypto = NEW.id_crypto 
            AND date_cours = NEW.date_transaction
            LIMIT 1
        ), -- depot is cost for the buyer
        0, -- no withdrawal for buyer
        NEW.date_transaction
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

--
-- after_transaction_crypto_insert
-- 
CREATE TRIGGER t_after_transaction_crypto_insert
AFTER INSERT ON transaction_crypto
FOR EACH ROW
EXECUTE FUNCTION f_update_transaction_fond();

--
-- user_crypto_wallet
--
CREATE OR REPLACE VIEW v_user_crypto_wallet AS
SELECT
    u.id AS user_id,
    u.name AS user_name,
    c.id AS crypto_id,
    c.label AS crypto_label,
    COALESCE(SUM(CASE WHEN u.id = tc.id_buyer THEN tc.qte_vendue ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN u.id = tc.id_seller THEN tc.qte_vendue ELSE 0 END), 0) AS crypto_balance,
    DATE(CURRENT_DATE) AS wallet_date
FROM
    users u
LEFT JOIN
    transaction_crypto tc ON u.id = tc.id_buyer OR u.id = tc.id_seller
LEFT JOIN
    crypto c ON c.id = tc.id_crypto
GROUP BY
    u.id, u.name, c.id, c.label;