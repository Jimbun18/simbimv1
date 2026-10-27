/**
 * Kompresi gambar sisi klien menggunakan HTML5 Canvas
 * Mengubah foto resolusi tinggi (misal 5-10MB dari kamera HP) menjadi file ringan (~150-300KB)
 * sebelum dikirimkan ke server.
 */
export async function compressImage(
  file: File,
  maxDimension: number = 1400,
  quality: number = 0.82
): Promise<File> {
  // Hanya proses jika file adalah gambar bitmap
  if (!file.type.startsWith('image/') || file.type === 'image/svg+xml') {
    return file
  }

  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onload = (readerEvent) => {
      const img = new Image()
      img.onload = () => {
        let width = img.width
        let height = img.height

        // Hitung skala rasio agar tidak melebihi dimensi batas
        if (width > maxDimension || height > maxDimension) {
          if (width >= height) {
            height = Math.round((height / width) * maxDimension)
            width = maxDimension
          } else {
            width = Math.round((width / height) * maxDimension)
            height = maxDimension
          }
        }

        const canvas = document.createElement('canvas')
        canvas.width = width
        canvas.height = height

        const ctx = canvas.getContext('2d')
        if (!ctx) {
          resolve(file)
          return
        }

        // Gambar ulang dengan resampling berkualitas
        ctx.imageSmoothingEnabled = true
        ctx.imageSmoothingQuality = 'high'
        ctx.drawImage(img, 0, 0, width, height)

        // Konversi ke format webp (atau jpeg jika webp tidak didukung browser lawas)
        canvas.toBlob(
          (blob) => {
            if (!blob) {
              resolve(file)
              return
            }

            const cleanName = file.name.replace(/\.[^/.]+$/, '') + '.webp'
            const compressedFile = new File([blob], cleanName, {
              type: 'image/webp',
              lastModified: Date.now(),
            })

            // Jika hasil kompresi ternyata lebih besar dari file aslinya, gunakan aslinya
            if (compressedFile.size > file.size) {
              resolve(file)
            } else {
              resolve(compressedFile)
            }
          },
          'image/webp',
          quality
        )
      }

      img.onerror = () => {
        resolve(file)
      }

      img.src = readerEvent.target?.result as string
    }

    reader.onerror = () => {
      resolve(file)
    }

    reader.readAsDataURL(file)
  })
}
