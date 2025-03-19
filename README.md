
# API Kehadiranmu

API untuk aplikasi absensi karyawan menggunakan JWT Firebase


## Installation 💻

- Duplikasi repositorinya / download ZIP-nya
- Import file sql yang ada didalam folder `database`
- Edit file `controllers/Absensi.php`, `controllers/Admin.php`, `controllers/Auth.php`, isi dengan secret key-mu sendiri dibaris berikut (harus sama)
  ```bash
  private $secret_key = "[FILL WITH YOUR OWN SECRET KEY]";
  ```
- Edit file `controllers/Auth.php`, isi dengan secret key-mu sendiri dibaris berikut (harus berbeda dari sebelumnya)
  ```bash
  private $refresh_secret_key = "[FILL IT WITH YOUR OWN SECRET KEY, BUT DIFFERENT FROM THE SECRET KEY]";
  ```
- Sesuaikan konfigurasi database `config/database.php`

## Support 🍵

Dukungan dalam rupiah:
[trakteer.id](https://trakteer.id/dedyk-suntoro-atmojo/tip) 

## License 🪪

MIT License

Copyright (c) 2025 Dedyk Suntoro Atmojo

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
