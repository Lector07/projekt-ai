@echo off
cd /d %~dp0
echo ===============================================
echo Dodawanie Environment Variables do Vercel
echo ===============================================
echo.

echo Dodawanie APP_KEY...
echo base64:uBja8oX8YdFH5IpZ6f7Mx1/LEwUacjsJzyQ/H321Aoo= | vercel env add APP_KEY production
echo base64:uBja8oX8YdFH5IpZ6f7Mx1/LEwUacjsJzyQ/H321Aoo= | vercel env add APP_KEY preview
echo base64:uBja8oX8YdFH5IpZ6f7Mx1/LEwUacjsJzyQ/H321Aoo= | vercel env add APP_KEY development

echo.
echo Dodawanie DB_HOST...
echo aws-1-eu-west-1.pooler.supabase.com | vercel env add DB_HOST production
echo aws-1-eu-west-1.pooler.supabase.com | vercel env add DB_HOST preview
echo aws-1-eu-west-1.pooler.supabase.com | vercel env add DB_HOST development

echo.
echo Dodawanie DB_DATABASE...
echo postgres | vercel env add DB_DATABASE production
echo postgres | vercel env add DB_DATABASE preview
echo postgres | vercel env add DB_DATABASE development

echo.
echo Dodawanie DB_USERNAME...
echo postgres.vlegdtrqlbbirpcbhdvr | vercel env add DB_USERNAME production
echo postgres.vlegdtrqlbbirpcbhdvr | vercel env add DB_USERNAME preview
echo postgres.vlegdtrqlbbirpcbhdvr | vercel env add DB_USERNAME development

echo.
echo Dodawanie DB_PASSWORD...
echo 8IWovpU90tV42ZKh | vercel env add DB_PASSWORD production
echo 8IWovpU90tV42ZKh | vercel env add DB_PASSWORD preview
echo 8IWovpU90tV42ZKh | vercel env add DB_PASSWORD development

echo.
echo ===============================================
echo Wszystkie Environment Variables zostaly dodane!
echo ===============================================
echo.
echo Mozesz teraz uruchomic: vercel --prod
echo.
pause

