Add-Type -AssemblyName System.Drawing

$sourcePath = "C:\laragon\www\simbim\frontend\public\logo.png"
if (-not (Test-Path $sourcePath)) {
    Write-Error "Source logo not found at $sourcePath"
    exit 1
}

$sourceImg = [System.Drawing.Image]::FromFile($sourcePath)

$densities = @(
    @{ Name = "mdpi";    LauncherSize = 48;  FgSize = 108 },
    @{ Name = "hdpi";    LauncherSize = 72;  FgSize = 162 },
    @{ Name = "xhdpi";   LauncherSize = 96;  FgSize = 216 },
    @{ Name = "xxhdpi";  LauncherSize = 144; FgSize = 324 },
    @{ Name = "xxxhdpi"; LauncherSize = 192; FgSize = 432 }
)

function Create-ResizedImage {
    param(
        [System.Drawing.Image]$source,
        [int]$targetWidth,
        [int]$targetHeight,
        [int]$padding = 0,
        [bool]$circular = $false
    )

    $bmp = New-Object System.Drawing.Bitmap $targetWidth, $targetHeight, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.Clear([System.Drawing.Color]::Transparent)

    if ($circular) {
        $path = New-Object System.Drawing.Drawing2D.GraphicsPath
        $path.AddEllipse(0, 0, $targetWidth, $targetHeight)
        $g.SetClip($path)
        $bgBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
        $g.FillPath($bgBrush, $path)
        $bgBrush.Dispose()
    }

    $destW = $targetWidth - ($padding * 2)
    $destH = $targetHeight - ($padding * 2)
    $destX = $padding
    $destY = $padding

    $g.DrawImage($source, $destX, $destY, $destW, $destH)
    $g.Dispose()

    return $bmp
}

# 1. Launcher Mipmaps
foreach ($d in $densities) {
    $folder = "C:\laragon\www\simbim\frontend\android\app\src\main\res\mipmap-$($d.Name)"
    if (-not (Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder -Force | Out-Null
    }

    Write-Host "Generating launcher icons for mipmap-$($d.Name)..."

    # ic_launcher.png
    $icLauncher = Create-ResizedImage -source $sourceImg -targetWidth $d.LauncherSize -targetHeight $d.LauncherSize -padding ([int]($d.LauncherSize * 0.05))
    $icLauncher.Save("$folder\ic_launcher.png", [System.Drawing.Imaging.ImageFormat]::Png)
    $icLauncher.Dispose()

    # ic_launcher_round.png
    $icRound = Create-ResizedImage -source $sourceImg -targetWidth $d.LauncherSize -targetHeight $d.LauncherSize -padding ([int]($d.LauncherSize * 0.08)) -circular $true
    $icRound.Save("$folder\ic_launcher_round.png", [System.Drawing.Imaging.ImageFormat]::Png)
    $icRound.Dispose()

    # ic_launcher_foreground.png (Adaptive icon)
    $fgPadding = [int]($d.FgSize * 0.17)
    $icFg = Create-ResizedImage -source $sourceImg -targetWidth $d.FgSize -targetHeight $d.FgSize -padding $fgPadding
    $icFg.Save("$folder\ic_launcher_foreground.png", [System.Drawing.Imaging.ImageFormat]::Png)
    $icFg.Dispose()
}

# 2. Splash Screens (Keep splash dimensions and center logo nicely)
$splashFiles = Get-ChildItem -Path "C:\laragon\www\simbim\frontend\android\app\src\main\res" -Filter "splash.png" -Recurse

foreach ($sf in $splashFiles) {
    $existing = [System.Drawing.Image]::FromFile($sf.FullName)
    $w = $existing.Width
    $h = $existing.Height
    $existing.Dispose()

    Write-Host "Generating splash for $($sf.Directory.Name) (${w}x${h})..."

    # Size logo proportional to minimum dimension (~35-40% of smallest edge)
    $minDim = [Math]::Min($w, $h)
    $logoSize = [int]($minDim * 0.40)
    if ($logoSize -lt 96) { $logoSize = 96 }
    if ($logoSize -gt $minDim) { $logoSize = $minDim }

    $bmp = New-Object System.Drawing.Bitmap $w, $h, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.Clear([System.Drawing.Color]::Transparent)

    $destX = [int](($w - $logoSize) / 2)
    $destY = [int](($h - $logoSize) / 2)

    $g.DrawImage($sourceImg, $destX, $destY, $logoSize, $logoSize)
    $g.Dispose()

    $bmp.Save($sf.FullName, [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
}

$sourceImg.Dispose()
Write-Host "Success! All Android launcher icons and splash screens updated to SDI Bina Insan Mandiri logo."
