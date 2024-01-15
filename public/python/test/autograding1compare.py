import subprocess
import difflib

def run_dart_analyze(flutter_code_path):
    try:
        process = subprocess.Popen(['dart', 'analyze', flutter_code_path],
                                   stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        stdout, stderr = process.communicate()
        return process.returncode, stdout, stderr
    except Exception as e:
        return -1, str(e), str(e)

def compare_code(student_code_path, kunci_jawaban_path):
    # Membaca isi file kode siswa dan kunci jawaban
    with open(student_code_path, 'r') as student_file:
        student_code = student_file.read()

    with open(kunci_jawaban_path, 'r') as kunci_jawaban_file:
        kunci_jawaban_code = kunci_jawaban_file.read()

    # Membandingkan kode siswa dengan kunci jawaban menggunakan difflib
    differ = difflib.Differ()
    diff = differ.compare(student_code.splitlines(), kunci_jawaban_code.splitlines())

    # Menampilkan perbedaan antara kode siswa dan kunci jawaban
    print("Perbedaan antara Kode Siswa dan Kunci Jawaban:")
    for line in diff:
        print(line)

def evaluate_flutter_code(student_code_path, kunci_jawaban_path):
    analyze_returncode, analyze_stdout, analyze_stderr = run_dart_analyze(student_code_path)

    # Membandingkan kode siswa dengan kunci jawaban
    compare_code(student_code_path, kunci_jawaban_path)

    # Mengembalikan hasil penilaian
    return {
        'analyze_returncode': analyze_returncode,
        'analyze_stdout': analyze_stdout,
        'analyze_stderr': analyze_stderr,
    }

def main():
    # Lokasi file kode Flutter siswa dan kunci jawaban
    student_code_path = 'public/flutter_application_1/lib/main2.dart'
    kunci_jawaban_path = 'public/flutter_application_1/lib/main.dart' # Sesuaikan dengan path kunci jawaban di proyek Anda

    # Menilai kode Flutter siswa
    evaluation_result = evaluate_flutter_code(student_code_path, kunci_jawaban_path)

    # Menampilkan hasil penilaian
    print("Dart Analyze Return Code:", evaluation_result['analyze_returncode'])
    print("Dart Analyze Output:")
    print(evaluation_result['analyze_stdout'])
    print("Dart Analyze Error:")
    print(evaluation_result['analyze_stderr'])

if __name__ == '__main__':
    main()
