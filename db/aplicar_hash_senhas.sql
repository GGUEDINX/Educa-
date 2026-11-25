-- ==========================================================
-- Aplica o hash (bcrypt) na senha "123456" dos 3 usuários de teste,
-- direto no seu banco atual. Rode no phpMyAdmin.
--
-- Observação: isso é só um "empurrãozinho" opcional — o sistema já
-- converte a senha de texto puro para hash sozinho, automaticamente,
-- na próxima vez que cada usuário fizer login. Rode isso só se quiser
-- que a senha já fique em hash no banco AGORA, sem esperar o login.
-- ==========================================================

UPDATE usuarios SET senha = '$2b$12$c2oH.MQlmbvA1SpDI4uT4.kplUFcKpVmB2hn3jSnMiSdKw7v.N01y'
WHERE senha = '123456';
