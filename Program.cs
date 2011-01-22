using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.IO;
using System.Diagnostics;

namespace RbDtaDataExtractor
{
    class Program
    {
        private static List<string> hopos = new List<string>();
        private static List<string> names = new List<string>();

        static void Main(string[] args)
        {

            DirectoryInfo di = new DirectoryInfo(Environment.CurrentDirectory);
            FileInfo[] files = di.GetFiles("*.dta");

            foreach (FileInfo file in files)
            {
                ProcessFile(file.OpenText());
            }

            names.Sort();
            hopos.Sort();

            foreach (string name in names)
            {
                Console.WriteLine(name);
            }

            Console.WriteLine("\n\n\n");

            foreach (string hopo in hopos)
            {
                Console.WriteLine(hopo);
            }
        }

        private static void ProcessFile(StreamReader reader)
        {
            string currentSong = "";
            string line;
            bool foundRealName = false;

            while ((line = reader.ReadLine()) != null)
            {
                if (line != "" && line[0] == '(')
                {
                    currentSong = line.Substring(1);
                    foundRealName = false;
                }
                if (line.Trim().StartsWith("(name") && !foundRealName)
                {
                    line = line.Trim();
                    string realname = line.Substring(7, line.IndexOf('"', 8) - 7);
                    names.Add("\t$NAMES[\"" + currentSong + "\"] = \"" + realname + "\";");
                    foundRealName = true;
                }
                if (line.Trim().StartsWith("(hopo_threshold"))
                {
                    line = line.Trim();
                    string realname = line.Substring(16, line.IndexOf(')', 17) - 16);
                    hopos.Add("\t$HOPOS[\"RB\"][\"" + currentSong + "\"] = \"" + realname + "\";");
                }
            }

            reader.Close();
        }
    }
}
